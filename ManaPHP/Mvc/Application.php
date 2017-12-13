<?php

namespace ManaPHP\Mvc;

/**
 * Class ManaPHP\Mvc\Application
 *
 * @package application
 * @property \ManaPHP\Http\RequestInterface   $request
 * @property \ManaPHP\Http\ResponseInterface  $response
 * @property \ManaPHP\ErrorHandlerInterface   $errorHandler
 * @property \ManaPHP\Mvc\RouterInterface     $router
 * @property \ManaPHP\Mvc\DispatcherInterface $dispatcher
 * @property \ManaPHP\Mvc\ViewInterface       $view
 * @property \ManaPHP\Http\SessionInterface   $session
 */
class Application extends \ManaPHP\Application
{
    public function __construct($loader, $dependencyInjector = null)
    {
        parent::__construct($loader, $dependencyInjector);
        $this->attachEvent('dispatcher:beforeDispatch', [$this, 'authorize']);
    }

    public function authenticate()
    {

    }

    /**
     * @param \ManaPHP\Mvc\DispatcherInterface $dispatcher
     */
    public function authorize($dispatcher)
    {

    }

    /**
     * @return \ManaPHP\Http\ResponseInterface
     * @throws \ManaPHP\Mvc\Exception
     */
    public function handle()
    {
        $this->registerServices();

        $this->authenticate();

        if (!$this->router->handle()) {
            throw new Exception('router does not have matched route for `:uri`'/**m0980aaf224562f1a4*/, ['uri' => $this->router->getRewriteUri()]);
        }

        $moduleName = $this->router->getModuleName();
        $controllerName = $this->router->getControllerName();
        $actionName = $this->router->getActionName();
        $params = $this->router->getParams();

        $this->alias->set('@module', '@app' . ($moduleName ? '/' . $moduleName : ''));
        $this->alias->set('@ns.module', '@ns.app' . ($moduleName ? '\\' . $moduleName : ''));
        $this->alias->set('@views', '@module/Views');
        $this->alias->set('@layouts', '@module/Views/Layouts');

        $ret = $this->dispatcher->dispatch($moduleName, $controllerName, $actionName, $params);
        if ($ret !== false) {
            $actionReturnValue = $this->dispatcher->getReturnedValue();
            if ($actionReturnValue === null) {
                $this->view->render($this->dispatcher->getControllerName(), $this->dispatcher->getActionName());
                $this->response->setContent($this->view->getContent());
            }
        }

        return $this->response;
    }

    public function main()
    {
        if ($this->configure->debug) {
            $this->handle();
        } else {
            try {
                $this->handle();
            } catch (\Exception $e) {
                $this->errorHandler->handleException($e);
            }
        }

        $this->response->send();
    }
}