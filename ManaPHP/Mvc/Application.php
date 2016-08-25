<?php

namespace ManaPHP\Mvc;

use ManaPHP\ApplicationInterface;
use ManaPHP\Component;
use ManaPHP\Di\FactoryDefault;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Mvc\Application\Exception;

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
     * @return bool
     * @throws \ManaPHP\Security\CsrfToken\Exception|\ManaPHP\Http\Request\Exception|\ManaPHP\Security\Crypt\Exception
     */
    public function _eventHandlerBeforeExecuteRoute()
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

        $controller = $this->dispatcher->dispatch($moduleName, $controllerName, $actionName, $params);

        if ($controller === false) {
            return $this->response;
        }

        $actionReturnValue = $this->dispatcher->getReturnedValue();

        if ($actionReturnValue === false) {
            return $this->response;
        } elseif ($actionReturnValue instanceof ResponseInterface) {
            return $actionReturnValue;
        } else {
            if ($actionReturnValue === null) {
                $content = '';
            } elseif (is_string($actionReturnValue)) {
                $content = $actionReturnValue;
            } else {
                throw new Exception('the return value of Action is invalid: ' . $actionReturnValue);
            }

            if ($this->_implicitView === true) {

                $this->view->setContent($content);
                $this->view->render($moduleName, $this->dispatcher->getControllerName(), $this->dispatcher->getActionName());
                $this->response->setContent($this->view->getContent());
            } else {
                $this->response->setContent($content);
            }

            return $this->response;
        }
    }
}