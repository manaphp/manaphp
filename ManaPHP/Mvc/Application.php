<?php

namespace ManaPHP\Mvc;

use ManaPHP\Mvc\Router\NotFoundRouteException;

/**
 * Class ManaPHP\Mvc\Application
 *
 * @package application
 *
 * @property \ManaPHP\Mvc\ViewInterface           $view
 * @property \ManaPHP\Mvc\Dispatcher              $dispatcher
 * @property \ManaPHP\Mvc\RouterInterface         $router
 * @property \ManaPHP\Http\RequestInterface       $request
 * @property \ManaPHP\Http\ResponseInterface      $response
 * @property \ManaPHP\Http\SessionInterface       $session
 * @property \ManaPHP\Security\CsrfTokenInterface $csrfToken
 */
class Application extends \ManaPHP\Application
{
    /**
     * @var \ManaPHP\Mvc\ModuleInterface
     */
    protected $_moduleObject;

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

        $r = $this->_moduleObject->authorize($this->dispatcher->getControllerName(), $this->dispatcher->getActionName());
        if ($r === false || is_object($r)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Handles a MVC request
     *
     * @param string $uri
     * @param string $method
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
     * @throws \ManaPHP\Mvc\Router\Exception
     * @throws \ManaPHP\Mvc\Router\NotFoundRouteException
     */
    public function handle($uri = null, $method = null)
    {
        if ($this->fireEvent('application:boot') === false) {
            return $this->response;
        }

        if (!$this->router->handle($uri, $method)) {
            throw new NotFoundRouteException('router does not have matched route for `:uri`'/**m0980aaf224562f1a4*/, ['uri' => $this->router->getRewriteUri($uri)]);
        }

        $moduleName = ucfirst($this->router->getModuleName());
        $controllerName = $this->router->getControllerName();
        $actionName = $this->router->getActionName();
        $params = $this->router->getParams();
        $this->alias->set('@views', "@app/$moduleName/Views");
        $this->alias->set('@messages', "@app/$moduleName/Messages");
        $this->alias->set('@ns.module', '@ns.app\\' . $moduleName);
        $this->alias->set('@ns.controllers', '@ns.module\\Controllers');
        $this->alias->set('@ns.widgets', '@ns.module\\Widgets');
        $moduleClassName = $this->alias->resolve('@ns.module\\Module');

        $eventData = ['module' => $moduleName];
        $this->fireEvent('application:beforeStartModule', $eventData);

        $this->_moduleObject = $this->_dependencyInjector->getShared(class_exists($moduleClassName) ? $moduleClassName : 'ManaPHP\Mvc\Module');
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
            $this->view->setContent($actionReturnValue);
            $this->view->render($this->dispatcher->getControllerName(), $this->dispatcher->getActionName());
            $this->response->setContent($this->view->getContent());
        }

        return $this->response;
    }

    public function main()
    {
        $this->registerServices();

        $this->configure->debug && $this->debugger->start();

        $this->handle();

        $this->response->send();
    }
}