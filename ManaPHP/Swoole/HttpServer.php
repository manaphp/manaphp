<?php
namespace ManaPHP\Swoole;

use ManaPHP\Application;
use ManaPHP\Utility\Text;
use Swoole\Exception as SwooleException;

/**
 * Class ManaPHP\Swoole\HttpServer
 *
 * @package application
 *
 * @property \ManaPHP\Http\ResponseInterface  $response
 * @property \ManaPHP\Mvc\RouterInterface     $router
 * @property \ManaPHP\Mvc\DispatcherInterface $dispatcher
 * @property \ManaPHP\Swoole\HttpStats        $httpStats
 * @property \ManaPHP\Http\CookiesInterface   $cookies
 * @property \ManaPHP\Mvc\ViewInterface       $view
 */
abstract class HttpServer extends Application
{
    /**
     * @var \swoole_http_server
     */
    protected $_swoole;

    /**
     * @var int
     */
    protected $_worker_num = 2;

    /**
     * @var bool
     */
    protected $_useModule = false;

    /**
     * @var bool
     */
    protected $_useCookie = true;

    /**
     * @var bool
     */
    protected $_useView = true;

    /**
     * @var string
     */
    protected $_listen = 'http://0.0.0.0:9501';

    public function __construct($loader, $dependencyInjector = null)
    {
        parent::__construct($loader, $dependencyInjector);
        $this->_createSwooleServer();

    }

    protected function _createSwooleServer()
    {
        $parts = parse_url($this->_listen);
        $scheme = $parts['scheme'];
        $host = $parts['host'];
        if (isset($parts['port'])) {
            $port = $parts['port'];
        } else {
            $port = ($scheme === 'http' ? 80 : 443);
        }

        if ($scheme === 'http') {
            $this->_swoole = new \swoole_http_server($host, $port);
        } else {
            $this->_swoole = new \swoole_http_server($host, $port, SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_TCP);
        }

        $this->_swoole->set(['worker_num' => $this->_worker_num]);

        $this->_dependencyInjector->setShared('httpStats', new HttpStats(['swoole' => $this->_swoole]));
        $this->_prepareSwoole();

        $this->_swoole->on('request', [$this, 'onRequest']);

    }

    protected function _prepareSwoole()
    {

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
        $this->httpStats->onBeforeRequest();
    }

    protected function _afterRequest()
    {
        $this->httpStats->onAfterRequest();
    }

    abstract public function authenticate();

    /**
     * @param \swoole_http_request  $request
     * @param \swoole_http_response $response
     */
    public function onRequest($request, $response)
    {
        $this->_prepareGlobals($request);

        $this->_beforeRequest();

        if ($_SERVER['REQUEST_URI'] === '/swoole-status') {
            $this->httpStats->handle();
        } else {
            $this->authenticate();

            if (!$this->router->handle()) {
                throw new SwooleException('router does not have matched route for `:uri`'/**m0980aaf224562f1a4*/, ['uri' => $this->router->getRewriteUri()]);
            }

            $router = $this->router;
            if ($this->_useModule) {
                $moduleName = $router->getModuleName();
                $controllerName = $router->getControllerName();
                $actionName = $router->getActionName();
                $params = $router->getParams();

                $this->alias->set('@module', '@app' . ($moduleName ? '/' . Text::camelize($moduleName) : ''));
                $this->alias->set('@ns.module', '@ns.app' . ($moduleName ? '\\' . Text::camelize($moduleName) : ''));
                $this->dispatcher->dispatch($moduleName, $controllerName, $actionName, $params);
            } else {
                $this->dispatcher->dispatch('', $router->getControllerName(), $router->getActionName(), $router->getParams());
            }

            $actionReturnValue = $this->dispatcher->getReturnedValue();
            if ($actionReturnValue === null && $this->_useView) {
                $this->alias->set('@views', '@module/Views');
                $this->alias->set('@layouts', '@app/Views/Layouts');

                $this->view->render($this->dispatcher->getControllerName(), $this->dispatcher->getActionName());
                $this->response->setContent($this->view->getContent());
            }
        }

        $response->header('worker-id', $_SERVER['WORKER_ID']);
        foreach ($this->response->getHeaders() as $k => $v) {
            $response->header($k, $v);
        }

        if ($this->_useCookie) {
            $this->fireEvent('cookies:beforeSend');
            foreach ($this->cookies->getSent() as $cookie) {
                $response->cookie($cookie['name'], $cookie['value'], $cookie['expire'],
                    $cookie['path'], $cookie['domain'], $cookie['secure'],
                    $cookie['httpOnly']);
            }
            $this->fireEvent('cookies:afterSend');
        }

        $content = $this->response->getContent();
        $response->end($content);
        $this->_dependencyInjector->reConstruct();

        $this->_afterRequest();
    }

    public function main()
    {
        $this->registerServices();

        $this->_swoole->start();
    }
}