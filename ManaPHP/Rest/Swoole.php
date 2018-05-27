<?php

namespace ManaPHP\Rest;

use ManaPHP\Application;
use ManaPHP\Swoole\Exception as SwooleException;

/**
 * Class ManaPHP\Rest\Swoole
 *
 * @package application
 *
 * @property \ManaPHP\Http\ResponseInterface  $response
 * @property \ManaPHP\RouterInterface         $router
 * @property \ManaPHP\Mvc\DispatcherInterface $dispatcher
 * @property \ManaPHP\Swoole\HttpStats        $httpStats
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
        $routerClass = $this->alias->resolveNS('@ns.app\Router');
        if (class_exists($routerClass)) {
            $this->_di->setShared('router', $routerClass);
        }
    }

    /**
     * @param \swoole_http_request $request
     */
    protected function _prepareGlobals($request)
    {
        static $server = null;

        if ($server === null) {
            $server = [];
            $script_filename = get_included_files()[0];
            $server['DOCUMENT_ROOT'] = dirname($script_filename);
            $server['SCRIPT_FILENAME'] = $script_filename;
            $server['PHP_SELF'] = $server['SCRIPT_NAME'] = '/' . basename($script_filename);
            $server['QUERY_STRING'] = '';
            $server['REQUEST_SCHEME'] = 'http';
            $parts = explode('-', phpversion());
            $server['SERVER_SOFTWARE'] = 'Swoole/' . SWOOLE_VERSION . ' ' . php_uname('s') . '/' . $parts[1] . ' PHP/' . $parts[0];
        }
        $_SERVER = array_change_key_case($request->server, CASE_UPPER);
        unset($_SERVER['SERVER_SOFTWARE']);

        $_SERVER += $server;

        foreach ($request->header ?: [] as $k => $v) {
            $_SERVER['HTTP_' . strtoupper(strtr($k, '-', '_'))] = $v;
        }

        $_SERVER['WORKER_ID'] = $this->_swoole->worker_pid;

        $_GET = $request->get ?: [];
        $_POST = $request->post ?: [];

        /** @noinspection AdditionOperationOnArraysInspection */
        $_REQUEST = $_POST + $_GET;

        $_COOKIE = $request->cookie ?: [];
        $_FILES = $request->files ?: [];
    }

    protected function _beforeRequest()
    {

    }

    protected function _afterRequest()
    {

    }

    /**
     * @param \swoole_http_request  $request
     * @param \swoole_http_response $response
     *
     * @throws \ManaPHP\Swoole\Exception
     */
    public function onRequest($request, $response)
    {
        if ($request->get('request_uri') === '/favicon.ico') {
            $response->status(404);
            $response->end();
            return;
        }

        $this->_prepareGlobals($request);

        $this->_beforeRequest();

        $this->identity->authenticate();

        if (!$this->router->handle()) {
            throw new SwooleException(['router does not have matched route for `:uri`', 'uri' => $this->router->getRewriteUri()]);
        }

        $router = $this->router;

        $this->dispatcher->dispatch($router->getControllerName(), $router->getActionName(), $router->getParams());
        $this->response->setHeader('X-Response-Time', round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3));
        $response->header('X-WORKER-ID', $_SERVER['WORKER_ID']);
        $headers = $this->response->getHeaders();
        if (isset($headers['Status'])) {
            $parts = explode(' ', $headers['Status']);
            $response->status($parts[0]);
            unset($headers['Status']);
        }
        foreach ($headers as $k => $v) {
            $response->header($k, $v);
        }

        $response->end($this->response->getContent());
        $this->_afterRequest();
        $this->_di->restoreInstancesState();
    }

    public function main()
    {
        $this->loader->registerFiles('@manaphp/helpers.php');

        if ($this->_dotenvFile && $this->filesystem->fileExists($this->_dotenvFile)) {
            $this->dotenv->load($this->_dotenvFile);
        }

        if ($this->_configFile) {
            $this->configure->loadFile($this->_configFile);
        }

        $swoole = new \swoole_http_server('0.0.0.0', 9501);
        $this->_di->setShared('swoole', $swoole);
        $swoole->set(['worker_num' => 2]);
        $swoole->on('request', [$this, 'onRequest']);

        $this->registerServices();

        $swoole->start();
    }
}