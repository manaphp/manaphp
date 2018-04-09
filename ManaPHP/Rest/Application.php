<?php

namespace ManaPHP\Rest;

use ManaPHP\Http\Response;
use ManaPHP\Router\NotFoundRouteException;

/**
 * Class ManaPHP\Mvc\Application
 *
 * @package application
 * @property \ManaPHP\Http\RequestInterface   $request
 * @property \ManaPHP\Http\ResponseInterface  $response
 * @property \ManaPHP\ErrorHandlerInterface   $errorHandler
 * @property \ManaPHP\RouterInterface         $router
 * @property \ManaPHP\Mvc\DispatcherInterface $dispatcher
 * @property \ManaPHP\Http\SessionInterface   $session
 */
class Application extends \ManaPHP\Application
{
    /**
     * Application constructor.
     *
     * @param \ManaPHP\Loader      $loader
     * @param \ManaPHP\DiInterface $di
     */
    public function __construct($loader, $di = null)
    {
        parent::__construct($loader, $di);

        $routerClass = $this->alias->resolveNS('@ns.app\Router');
        if (class_exists($routerClass)) {
            $this->_di->setShared('router', $routerClass);
        }
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
     * @return \ManaPHP\Http\ResponseInterface
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
            if ($actionReturnValue instanceof Response) {
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

        try {
            $this->handle();
        } /** @noinspection PhpUndefinedClassInspection */
        catch (\Exception $e) {
            $this->errorHandler->handle($e);
        } catch (\Error $e) {
            $this->errorHandler->handle($e);
        }

        $this->response->setHeader('X-Response-Time', round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3));
        $this->response->send();
    }
}