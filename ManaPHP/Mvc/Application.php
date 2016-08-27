<?php

namespace ManaPHP\Mvc;

use ManaPHP\ApplicationInterface;
use ManaPHP\Component;
use ManaPHP\Di\FactoryDefault;

/**
 * ManaPHP\Mvc\Application
 *
 * This component encapsulates all the complex operations behind instantiating every component
 * needed and integrating it with the rest to allow the MVC pattern to operate as desired.
 *
 *
 * @property \ManaPHP\Loader             $loader
 * @property \ManaPHP\Mvc\View           $view
 * @property \ManaPHP\Mvc\Dispatcher     $dispatcher
 * @property \ManaPHP\Mvc\Router         $router
 * @property \ManaPHP\Http\Request       $request
 * @property \ManaPHP\Http\Response      $response
 * @property \ManaPHP\Http\Session       $session
 * @property \ManaPHP\Debugger           $debugger
 * @property \Application\Configure      $configure
 * @property \ManaPHP\Log\Logger         $logger
 * @property \ManaPHP\Security\CsrfToken $csrfToken
 */
class Application extends Component implements ApplicationInterface
{
    /**
     * @var boolean
     */
    protected $_implicitView = true;

    /**
     * @var \ManaPHP\Mvc\ModuleInterface
     */
    protected $_moduleObject;

    /**
     * @var bool
     */
    protected $_useCachedResponse;

    /**
     * \ManaPHP\Mvc\Application
     *
     * @param \ManaPHP\DiInterface $dependencyInjector
     */
    public function __construct($dependencyInjector = null)
    {
        $this->_dependencyInjector = $dependencyInjector ?: new FactoryDefault();

        $this->_dependencyInjector->setShared('application', $this);
    }

    /**
     * By default. The view is implicitly buffering all the output
     * You can full disable the view component using this method
     *
     * @param boolean $implicitView
     *
     * @return static
     */
    public function useImplicitView($implicitView)
    {
        $this->_implicitView = $implicitView;

        return $this;
    }

    /**
     * @param \ManaPHP\Mvc\Dispatcher $dispatcher
     *
     * @return bool
     * @throws \ManaPHP\Security\CsrfToken\Exception|\ManaPHP\Http\Request\Exception|\ManaPHP\Security\Crypt\Exception
     */
    public function _eventHandlerBeforeExecuteRoute($dispatcher)
    {
        $ignoreMethods = ['GET', 'HEAD', 'OPTIONS'];
        if (isset($this->csrfToken)
            && !in_array($this->request->getMethod(), $ignoreMethods, true)
        ) {
            $this->csrfToken->verify();
        }

        /** @noinspection IfReturnReturnSimplificationInspection */
        if ($this->_moduleObject->authorize($this->dispatcher->getControllerName(), $this->dispatcher->getActionName()) === false) {
            return false;
        }

        if ($content = $dispatcher->getController()->getCachedResponse($dispatcher->getActionName())) {
            $this->_useCachedResponse = true;
            $dispatcher->setReturnedValue($dispatcher->getController()->response->setContent($content));
            return false;
        } else {
            $this->_useCachedResponse = false;
        }

        return true;
    }

    /**
     * Handles a MVC request
     *
     * @param string $uri
     *
     * @return \ManaPHP\Http\ResponseInterface
     * @throws \ManaPHP\Mvc\Application\Exception|\ManaPHP\Event\Exception|\ManaPHP\Mvc\Application\NotFoundModuleException|\ManaPHP\Mvc\Dispatcher\Exception|\ManaPHP\Mvc\Dispatcher\NotFoundControllerException|\ManaPHP\Mvc\Dispatcher\NotFoundActionException|\ManaPHP\Mvc\View\Exception|\ManaPHP\Renderer\Exception|\ManaPHP\Alias\Exception|\ManaPHP\Mvc\Router\Exception|\ManaPHP\Mvc\Router\NotFoundRouteException
     */
    public function handle($uri = null)
    {
        if ($this->fireEvent('application:boot') === false) {
            return $this->response;
        }

        $this->router->handle($uri, null, false);

        $moduleName = ucfirst($this->router->getModuleName());
        $controllerName = $this->router->getControllerName();
        $actionName = $this->router->getActionName();
        $params = $this->router->getParams();

        $moduleClassName = basename($this->alias->get('@app')) . "\\$moduleName\\Module";

        $eventData = ['module' => $moduleName];
        $this->fireEvent('application:beforeStartModule', $eventData);
        $this->_moduleObject = $this->_dependencyInjector->getShared($moduleClassName);
        $this->_moduleObject->registerServices($this->_dependencyInjector);

        $eventData = ['module' => $moduleName];
        $this->fireEvent('application:afterStartModule', $eventData);

        if ($this->dispatcher->getRootNamespace() === null) {
            $this->dispatcher->setRootNamespace(basename($this->alias->get('@app')));
        }

        $handler = [$this, '_eventHandlerBeforeExecuteRoute'];
        $this->dispatcher->attachEvent('dispatcher:beforeExecuteRoute', $handler);

        $this->dispatcher->dispatch($moduleName, $controllerName, $actionName, $params);

        $actionReturnValue = $this->dispatcher->getReturnedValue();
        if ($actionReturnValue === null || is_string($actionReturnValue)) {
            if ($this->_implicitView === true) {

                $this->view->setContent($actionReturnValue);
                $this->view->render($this->dispatcher->getModuleName(), $this->dispatcher->getControllerName(), $this->dispatcher->getActionName());
                $this->response->setContent($this->view->getContent());
            } else {
                $this->response->setContent($actionReturnValue);
            }
        }

        if (!$this->_useCachedResponse) {
            $this->dispatcher->getController()->setCachedResponse($this->dispatcher->getActionName(), $this->response->getContent());
        }

        return $this->response;
    }
}