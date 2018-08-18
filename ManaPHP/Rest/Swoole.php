<?php

namespace ManaPHP\Rest;

use ManaPHP\Application;
use ManaPHP\Swoole\Exception as SwooleException;

/**
 * Class ManaPHP\Rest\Swoole
 *
 * @package application
 *
 * @property \ManaPHP\Http\ResponseInterface     $response
 * @property \ManaPHP\RouterInterface            $router
 * @property \ManaPHP\Mvc\DispatcherInterface    $dispatcher
 * @property \ManaPHP\Swoole\HttpServerInterface $swooleHttpServer
 */
class Swoole extends Application
{
    /**
     * HttpServer constructor.
     *
     * @param  \ManaPHP\Loader     $loader
     */
    public function __construct($loader)
    {
        parent::__construct($loader);
        $this->_di->keepInstanceState();
    }

    protected function _beforeRequest()
    {

    }

    protected function _afterRequest()
    {

    }

    public function authenticate()
    {
        return $this->identity->authenticate();
    }

    /**
     * @throws \ManaPHP\Swoole\Exception
     */
    public function handle()
    {
        $this->_beforeRequest();

        $this->authenticate();

        if (!$this->router->handle()) {
            throw new SwooleException(['router does not have matched route for `:uri`', 'uri' => $this->router->getRewriteUri()]);
        }

        $router = $this->router;
        $this->dispatcher->dispatch($router->getControllerName(), $router->getActionName(), $router->getParams());
        $this->swooleHttpServer
            ->sendHeaders($this->response->getHeaders())
            ->sendContent($this->response->getContent());
        $this->_afterRequest();
        $this->_di->restoreInstancesState();
    }

    public function main()
    {
        $this->dotenv->load();
        $this->configure->load();

        $this->registerServices();

        $this->swooleHttpServer->start([$this, 'handle']);
    }
}