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
     * @param \ManaPHP\DiInterface $di
     */
    public function __construct($loader, $di = null)
    {
        parent::__construct($loader, $di);
        $this->_di->keepInstanceState();
    }

    protected function _beforeRequest()
    {

    }

    protected function _afterRequest()
    {

    }

    /**
     */
    public function handle()
    {
        $this->_beforeRequest();

        $this->identity->authenticate();

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
        if ($this->_dotenvFile && $this->filesystem->fileExists($this->_dotenvFile)) {
            $this->dotenv->load($this->_dotenvFile);
        }

        if ($this->_configFile) {
            $this->configure->loadFile($this->_configFile);
        }

        $this->registerServices();

        $this->swooleHttpServer->start([$this, 'handle']);
    }
}