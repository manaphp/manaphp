<?php

namespace ManaPHP\Mvc;

use ManaPHP\Di\FactoryDefault;

/**
 * ManaPHP\Mvc\Application
 *
 * This component encapsulates all the complex operations behind instantiating every component
 * needed and integrating it with the rest to allow the MVC pattern to operate as desired.
 *
 *
 * @property \ManaPHP\Mvc\ViewInterface           $view
 * @property \ManaPHP\Mvc\Dispatcher              $dispatcher
 * @property \ManaPHP\Mvc\RouterInterface         $router
 * @property \ManaPHP\Http\RequestInterface       $request
 * @property \ManaPHP\Http\ResponseInterface      $response
 * @property \ManaPHP\Http\SessionInterface       $session
 * @property \ManaPHP\Security\CsrfTokenInterface $csrfToken
 */
abstract class Application extends \ManaPHP\Application
{
    /**
     * @var bool
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
     * @param bool $implicitView
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
     * @throws \ManaPHP\Security\CsrfToken\Exception
     * @throws \ManaPHP\Http\Request\Exception
     * @throws \ManaPHP\Security\Crypt\Exception
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
     * @throws \ManaPHP\Mvc\Application\Exception
     * @throws \ManaPHP\Event\Exception
     * @throws \ManaPHP\Mvc\Application\NotFoundModuleException
     * @throws \ManaPHP\Mvc\Dispatcher\Exception
     * @throws \ManaPHP\Mvc\Dispatcher\NotFoundControllerException
     * @throws \ManaPHP\Mvc\Dispatcher\NotFoundActionException
     * @throws \ManaPHP\Mvc\View\Exception
     * @throws \ManaPHP\Renderer\Exception
     * @throws \ManaPHP\Alias\Exception
     * @throws \ManaPHP\Mvc\Router\Exception|
     * @throws \ManaPHP\Mvc\Router\NotFoundRouteException
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
        $this->alias->set('@views', "@app/$moduleName/Views");
        $this->alias->set('@ns.module', '@ns.app\\' . $moduleName);
        $this->alias->set('@ns.controllers', '@ns.module\\Controllers');
        $this->alias->set('@ns.widgets', '@ns.module\\Widgets');
        $moduleClassName = $this->alias->resolve('@ns.module\\Module');

        $eventData = ['module' => $moduleName];
        $this->fireEvent('application:beforeStartModule', $eventData);
        $this->_moduleObject = $this->_dependencyInjector->getShared($moduleClassName);
        $this->_moduleObject->registerServices($this->_dependencyInjector);

        $eventData = ['module' => $moduleName];
        $this->fireEvent('application:afterStartModule', $eventData);

        $handler = [$this, '_eventHandlerBeforeExecuteRoute'];
        $this->dispatcher->attachEvent('dispatcher:beforeExecuteRoute', $handler);

        $ret = $this->dispatcher->dispatch($moduleName, $controllerName, $actionName, $params);
        if ($ret === false) {
            return $this->response;
        }

        $actionReturnValue = $this->dispatcher->getReturnedValue();
        if ($actionReturnValue === null || is_string($actionReturnValue)) {
            if ($this->_implicitView === true) {

                $this->view->setContent($actionReturnValue);
                $this->view->render($this->dispatcher->getControllerName(), $this->dispatcher->getActionName());
                $this->response->setContent($this->view->getContent());
            } else {
                $this->response->setContent($actionReturnValue);
            }
        }

        return $this->response;
    }
}