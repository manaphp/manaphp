<?php
namespace ManaPHP\Swoole\Http;

use ManaPHP\Component;

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

    /**
     * Http constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['host'])) {
            $this->_host = $options['host'];
            unset($options['host']);
        }
        if (isset($options['port'])) {
            $this->_port = (int)$options['port'];
            unset($options['port']);
        }

        $this->alias->set('@web', '');

        $options['enable_coroutine'] = MANAPHP_COROUTINE ? true : false;

        $this->_settings = $options;

        $script_filename = get_included_files()[0];
        $parts = explode('-', phpversion());
        $_SERVER = [
            'DOCUMENT_ROOT' => dirname($script_filename),
            'SCRIPT_FILENAME' => $script_filename,
            'SCRIPT_NAME' => '/' . basename($script_filename),
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

    /**
     * @param callable|array $handler
     *
     * @return static
     */
    public function start($handler)
    {
        $this->_swoole = new \swoole_http_server($this->_host, $this->_port);
        $this->_swoole->set($this->_settings);
        $this->_handler = $handler;

        echo PHP_EOL, str_repeat('+', 80), PHP_EOL;
        echo sprintf('[%s][info]: starting listen on: %s:%d with setting: %s', date('c'), $this->_host, $this->_port, json_encode($this->_settings)), PHP_EOL;

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
        } catch (\Exception $exception) {
            $this->logger->error($exception);
            $response->status(500);
            $response->end('Internal Server Error');
        } catch (\Error $error) {
            $this->logger->error($error);
            $response->status(500);
            $response->end('Internal Server Error');
        }
    }

    /**
     * @param int $code
     *
     * @return static
     */
    public function setStatus($code)
    {
        $context = $this->_context;

        $context->response->status($code);

        return $this;
    }

    /**
     * @param array $headers
     *
     * @return static
     */
    public function sendHeaders($headers)
    {
        $response = $this->_context->response;

        foreach ($headers as $k => $v) {
            $response->header($k, $v, false);
        }

        return $this;
    }

    /**
     * @param array $cookies
     *
     * @return static
     */
    public function sendCookies($cookies)
    {
        $response = $this->_context->response;

        foreach ($cookies as $cookie) {
            $response->cookie($cookie['name'], $cookie['value'], $cookie['expire'],
                $cookie['path'], $cookie['domain'], $cookie['secure'],
                $cookie['httpOnly']);
        }

        return $this;
    }

    /**
     * @param string $content
     *
     * @return static
     */
    public function sendContent($content)
    {
        $this->_context->response->end($content);
        return $this;
    }

    /**
     * @param string $file
     *
     * @return static
     */
    public function sendFile($file)
    {
        $this->_context->response->sendfile($this->alias->resolve($file));

        return $this;
    }

    /**
     * @param \ManaPHP\Http\ResponseInterface $response
     */
    public function send($response)
    {
        if (($request_id = $this->request->getServer('HTTP_X_REQUEST_ID')) && !$response->hasHeader('X-Request-Id')) {
            $response->setHeader('X-Request-Id', $request_id);
        }

        $response->setHeader('X-Response-Time', sprintf('%.3f', microtime(true) - $this->request->getServer('REQUEST_TIME_FLOAT')));

        $this->eventsManager->fireEvent('response:beforeSend', $this, $response);

        $this->setStatus($response->getStatusCode());
        $this->sendHeaders($response->getHeaders());

        if ($file = $response->getFile()) {
            $this->sendFile($file);
        } else {
            $this->sendContent($response->getContent());
        }

        $this->eventsManager->fireEvent('response:afterSend', $this, $response);
    }
}