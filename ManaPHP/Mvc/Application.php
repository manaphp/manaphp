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

    public function beforeStartModule()
    {
        $ignoreMethods = ['GET', 'HEAD', 'OPTIONS'];
        if (isset($this->csrfToken) && !in_array($this->request->getMethod(), $ignoreMethods, true)
        ) {
            $this->csrfToken->verify();
        }
    }

    /**
     * @return bool
     * @throws \ManaPHP\Security\CsrfToken\Exception
     * @throws \ManaPHP\Http\Request\Exception
     * @throws \ManaPHP\Security\Crypt\Exception
     */
    public function onDispatcherBeforeExecuteRoute()
    {
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
        if (!$this->router->handle($uri, $method)) {
            throw new NotFoundRouteException('router does not have matched route for `:uri`'/**m0980aaf224562f1a4*/, ['uri' => $this->router->getRewriteUri($uri)]);
        }

        $moduleName = ucfirst($this->router->getModuleName());
        $controllerName = $this->router->getControllerName();
        $actionName = $this->router->getActionName();
        $params = $this->router->getParams();
        $this->alias->set('@module', "@app/$moduleName");
        $this->alias->set('@ns.module', '@ns.app\\' . $moduleName);
        $this->alias->set('@views', '@module/Views');

        $moduleClassName = $this->alias->resolveNS('@ns.module\\Module');

        $this->beforeStartModule();

        $this->attachEvent('dispatcher:beforeExecuteRoute');

        $this->fireEvent('application:beforeStartModule');

        $this->_moduleObject = $this->_dependencyInjector->getShared(class_exists($moduleClassName) ? $moduleClassName : 'ManaPHP\Mvc\Module');
        $this->_moduleObject->registerServices($this->_dependencyInjector);

        $this->fireEvent('application:afterStartModule');

        $ret = $this->dispatcher->dispatch($moduleName, $controllerName, $actionName, $params);
        if ($ret !== false) {
            $actionReturnValue = $this->dispatcher->getReturnedValue();
            if ($actionReturnValue === null || is_string($actionReturnValue)) {
                $this->view->setContent($actionReturnValue);
                $this->view->render($this->dispatcher->getControllerName(), $this->dispatcher->getActionName());
                $this->response->setContent($this->view->getContent());
            }
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