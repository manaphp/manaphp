<?php
namespace ManaPHP\Swoole;

/**
 * Class ManaPHP\Mvc\Application
 *
 * @package application
 *
 * @property \ManaPHP\Http\ResponseInterface $response
 * @property \ManaPHP\Http\CookiesInterface  $cookies
 * @property \ManaPHP\Mvc\HandlerInterface   $mvcHandler
 */
class HttpServer extends \ManaPHP\Application
{
    /**
     * @var \swoole_http_server
     */
    protected $_swoole;

    /**
     * @var string
     */
    protected $_listen = 'http://0.0.0.0:9501';

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
     */
    public function onRequest($request, $response)
    {
        $this->_prepareGlobals($request);
        $this->_beforeRequest();

        if (1) {
            $this->mvcHandler->handle();
            $content = $this->response->getContent();
            //      $this->debugger->save();
        } else {
            $content = json_encode(['time' => date('Y-m-d H:i:s')]);
        }
        $this->response->setHeader('worker-id', $_SERVER['WORKER_ID']);
        foreach ($this->response->getHeaders() as $k => $v) {
            $response->header($k, $v);
        }

        $this->fireEvent('cookies:beforeSend');
        foreach ($this->cookies->getSent() as $cookie) {
            $response->cookie($cookie['name'], $cookie['value'], $cookie['expire'],
                $cookie['path'], $cookie['domain'], $cookie['secure'],
                $cookie['httpOnly']);
        }
        $this->fireEvent('cookies:afterSend');
        $response->end($content);
        $this->_dependencyInjector->reConstruct();
        $this->_afterRequest();
    }

    /**
     * @param \swoole_http_server $swoole
     */
    protected function _prepareSwoole($swoole)
    {

    }

    public function main()
    {
        $this->registerServices();

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

        $this->_prepareSwoole($this->_swoole);

        $this->_swoole->on('request', [$this, 'onRequest']);

        $this->_swoole->start();
    }
}