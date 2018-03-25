<?php

namespace ManaPHP\Mvc;

use ManaPHP\Http\Response;
use ManaPHP\Mvc\Router\NotFoundRouteException;

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
    /**
     * Application constructor.
     *
     * @param \ManaPHP\Loader      $loader
     * @param \ManaPHP\DiInterface $dependencyInjector
     */
    public function __construct($loader, $dependencyInjector = null)
    {
        parent::__construct($loader, $dependencyInjector);
        $this->attachEvent('dispatcher:beforeDispatch', [$this, 'authorize']);
    }

    /**
     * @return string
     */
    public function getAppName()
    {
        return basename($this->alias->resolveNS('@ns.app'));
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
        $this->authenticate();

        if (!$this->router->handle()) {
            throw new NotFoundRouteException(['router does not have matched route for `:uri`'/**m0980aaf224562f1a4*/, 'uri' => $this->router->getRewriteUri()]);
        }

        $controllerName = $this->router->getControllerName();
        $actionName = $this->router->getActionName();
        $params = $this->router->getParams();

        $ret = $this->dispatcher->dispatch($controllerName, $actionName, $params);
        if ($ret !== false) {
            $actionReturnValue = $this->dispatcher->getReturnedValue();
            if ($actionReturnValue === null || $actionReturnValue instanceof View) {
                $this->view->render($this->dispatcher->getControllerName(), $this->dispatcher->getActionName());
                $this->response->setContent($this->view->getContent());
            } elseif ($actionReturnValue instanceof Response) {
                null;
            } else {
                $this->response->setJsonContent($actionReturnValue);
            }
        }

        return $this->response;
    }

    public function main()
    {
        $this->registerServices();

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