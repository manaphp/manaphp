<?php
namespace ManaPHP\Swoole\Http;

use ManaPHP\Component;
use ManaPHP\ContextManager;

class ServerContext
{
    /**
     * @var \swoole_http_request
     */
    public $request;

    /**
     * @var \swoole_http_response
     */
    public $response;
}

/**
 * Class Server
 * @package ManaPHP\Swoole\Http
 * @property-read \ManaPHP\Http\RequestInterface $request
 * @property \ManaPHP\Swoole\Http\ServerContext  $_context
 */
class Server extends Component implements ServerInterface
{
    /**
     * @var string
     */
    protected $_host = '0.0.0.0';

    /**
     * @var int
     */
    protected $_port = 9501;

    /**
     * @var array
     */
    protected $_settings = [];

    /**
     * @var array
     */
    protected $_server = [];

    /**
     * @var \swoole_http_server
     */
    protected $_swoole;

    /**
     * @var callable|array
     */
    protected $_handler;

    public function __construct()
    {
        $script_filename = get_included_files()[0];
        $parts = explode('-', phpversion());
        $_SERVER = [
            'DOCUMENT_ROOT' => dirname($script_filename),
            'SCRIPT_FILENAME' => $script_filename,
            'SCRIPT_NAME' => '/' . basename($script_filename),
            'SERVER_ADDR' => $this->_host,
            'SERVER_SOFTWARE' => 'Swoole/' . SWOOLE_VERSION . ' ' . php_uname('s') . '/' . $parts[1] . ' PHP/' . $parts[0]
        ];

        $this->_server = $_SERVER + [
                'PHP_SELF' => '/' . basename($script_filename),
                'QUERY_STRING' => '',
                'REQUEST_SCHEME' => 'http',
            ];

        unset($_GET, $_POST, $_REQUEST, $_FILES, $_COOKIE);
    }

    /**
     * @param \swoole_http_request $request
     */
    public function _prepareGlobals($request)
    {
        $_server = array_change_key_case($request->server, CASE_UPPER);
        unset($_server['SERVER_SOFTWARE']);
        /** @noinspection AdditionOperationOnArraysInspection */
        $_server += $this->_server;

        foreach ($request->header ?: [] as $k => $v) {
            if (in_array($k, ['content-type', 'content-length'], true)) {
                $_server[strtoupper(strtr($k, '-', '_'))] = $v;
            } else {
                $_server['HTTP_' . strtoupper(strtr($k, '-', '_'))] = $v;
            }
        }

        $_get = $request->get ?: [];
        $request_uri = $_server['REQUEST_URI'];
        $_get['_url'] = ($pos = strpos($request_uri, '?')) ? substr($request_uri, 0, $pos) : $request_uri;

        $_post = $request->post ?: [];

        if (!$_post && isset($_server['REQUEST_METHOD']) && !in_array($_server['REQUEST_METHOD'], ['GET', 'OPTIONS'], true)) {
            $data = $request->rawContent();

            if (isset($_server['CONTENT_TYPE']) && strpos($_server['CONTENT_TYPE'], 'application/json') !== false) {
                $_post = json_decode($data, true, 16);
            } else {
                parse_str($data, $_post);
            }
            if (!is_array($_post)) {
                $_post = [];
            }
        }

        $globals = $this->request->getGlobals();

        $globals->_GET = $_get;
        $globals->_POST = $_post;
        /** @noinspection AdditionOperationOnArraysInspection */
        $globals->_REQUEST = $_post + $_get;
        $globals->_SERVER = $_server;
        $globals->_COOKIE = $request->cookie ?: [];
        $globals->_FILES = $request->files ?: [];

        if ($this->configure->compatible_globals) {
            $_GET = $globals->_GET;
            $_POST = $globals->_POST;
            $_REQUEST = $globals->_REQUEST;
            $_SERVER = $globals->_SERVER;
            $_COOKIE = $globals->_COOKIE;
            $_FILES = $globals->_FILES;
        }
    }

    public function log($level, $message)
    {
        echo sprintf('[%s][%s]: ', date('c'), $level), $message, PHP_EOL;
    }

    /**
     * @param callable|array $handler
     *
     * @return static
     */
    public function start($handler)
    {
        echo PHP_EOL, str_repeat('+', 80), PHP_EOL;

        $settings = isset($this->configure->servers['http']) ? $this->configure->servers['http'] : [];

        if (!empty($settings['enable_static_handler'])) {
            $settings['document_root'] = $_SERVER['DOCUMENT_ROOT'];
            if ($this->configure->debug) {
                $this->log('warn', 'enable `enable_static_handler` setting of swoole will reduce performance!!!!');
                sleep(3);
            }
        }

        if (isset($settings['host'])) {
            $this->_host = $settings['host'];
        }

        if (isset($settings['port'])) {
            $this->_port = (int)$settings['port'];
        }

        $settings['enable_coroutine'] = MANAPHP_COROUTINE ? true : false;

        $this->_settings = $settings;

        $this->_swoole = new \swoole_http_server($this->_host, $this->_port);
        $this->_swoole->set($this->_settings);
        $this->_handler = $handler;

        $this->log('info',
            sprintf('starting listen on: %s:%d with setting: %s', $this->_host, $this->_port, json_encode($this->_settings, JSON_UNESCAPED_SLASHES)));

        $this->_swoole->on('request', [$this, 'onRequest']);

        $this->_swoole->start();
        echo sprintf('[%s][info]: shutdown', date('c')), PHP_EOL;

        return $this;
    }

    /**
     * @param \swoole_http_request  $request
     * @param \swoole_http_response $response
     */
    public function onRequest($request, $response)
    {
        try {
            if ($request->server['request_uri'] === '/favicon.ico') {
                $response->status(404);
                $response->end();
                return;
            }
            $context = $this->_context;

            $context->request = $request;
            $context->response = $response;

            $this->_prepareGlobals($request);

            if (is_array($this->_handler)) {
                $this->_handler[0]->{$this->_handler[1]}();
            } else {
                $method = $this->_handler;
                $method();
            }
        } catch (\Throwable $exception) {
            $str = date('c') . ' ' . get_class($exception) . ': ' . $exception->getMessage() . PHP_EOL;
            $str .= '    at ' . $exception->getFile() . ':' . $exception->getLine() . PHP_EOL;
            $traces = $exception->getTraceAsString();
            $str .= preg_replace('/#\d+\s/', '    at ', $traces);
            echo $str . PHP_EOL;
        } finally {
            ContextManager::reset();
        }
    }

    /**
     * @param \ManaPHP\Http\ResponseInterface $response
     */
    public function send($response)
    {
        $this->eventsManager->fireEvent('response:beforeSend', $this, $response);

        /** @var \ManaPHP\Http\Response $response */
        $response_context = $response->_context;
        $sw_response = $this->_context->response;

        $sw_response->status($response_context->status_code);

        foreach ($response_context->headers as $name => $value) {
            $sw_response->header($name, $value, false);
        }

        $server = $this->request->getGlobals()->_SERVER;

        if (isset($server['HTTP_X_REQUEST_ID']) && !isset($response_context->headers['X-Request-Id'])) {
            $sw_response->header('X-Request-Id', $server['HTTP_X_REQUEST_ID'], false);
        }

        $sw_response->header('X-Response-Time', sprintf('%.3f', microtime(true) - $server['REQUEST_TIME_FLOAT']), false);

        foreach ($response_context->cookies as $cookie) {
            $sw_response->cookie($cookie['name'], $cookie['value'], $cookie['expire'],
                $cookie['path'], $cookie['domain'], $cookie['secure'],
                $cookie['httpOnly']);
        }

        if ($response_context->file) {
            $sw_response->sendfile($this->alias->resolve($response_context->file));
        } else {
            $sw_response->end($response_context->content);
        }

        $this->eventsManager->fireEvent('response:afterSend', $this, $response);
    }
}