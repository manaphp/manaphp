<?php
namespace ManaPHP\Swoole\Http;

use ManaPHP\Component;

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
     * @var \swoole_http_request
     */
    protected $_request;

    /**
     * @var \swoole_http_response
     */
    protected $_response;

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

        $this->_settings = $options;

        $script_filename = get_included_files()[0];
        $parts = explode('-', phpversion());
        $this->_server = [
            'DOCUMENT_ROOT' => dirname($script_filename),
            'SCRIPT_FILENAME' => $script_filename,
            'SCRIPT_NAME' => '/' . basename($script_filename),
            'PHP_SELF' => '/' . basename($script_filename),
            'QUERY_STRING' => '',
            'REQUEST_SCHEME' => 'http',
            'SERVER_SOFTWARE' => 'Swoole/' . SWOOLE_VERSION . ' ' . php_uname('s') . '/' . $parts[1] . ' PHP/' . $parts[0]
        ];
    }

    /**
     * @param \swoole_http_request $request
     */
    public function _prepareGlobals($request)
    {
        $_SERVER = array_change_key_case($request->server, CASE_UPPER);
        unset($_SERVER['SERVER_SOFTWARE']);
        $_SERVER += $this->_server;

        foreach ($request->header ?: [] as $k => $v) {
            if (in_array($k, ['content-type', 'content-length'], true)) {
                $_SERVER[strtoupper(strtr($k, '-', '_'))] = $v;
            } else {
                $_SERVER['HTTP_' . strtoupper(strtr($k, '-', '_'))] = $v;
            }
        }

        $_GET = $request->get ?: [];
        $request_uri = $_SERVER['REQUEST_URI'];
        $_GET['_url'] = ($pos = strpos($request_uri, '?')) ? substr($request_uri, 0, $pos) : $request_uri;

        $_POST = $request->post ?: [];

        /** @noinspection AdditionOperationOnArraysInspection */
        $_REQUEST = $_POST + $_GET;

        $_COOKIE = $request->cookie ?: [];
        $_FILES = $request->files ?: [];

        if (!$_POST && isset($_SERVER['REQUEST_METHOD']) && !in_array($_SERVER['REQUEST_METHOD'], ['GET', 'OPTIONS'], true)) {
            $data = $request->rawContent();

            if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
                $_POST = json_decode($data, true, 32);
            } else {
                parse_str($data, $_POST);
            }

            if (is_array($_POST)) {
                $_REQUEST = array_merge($_GET, $_POST);
            } else {
                $_POST = [];
            }
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
            $this->_request = $request;
            $this->_response = $response;
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
        $this->_response->status($code);

        return $this;
    }

    /**
     * @param array $headers
     *
     * @return static
     */
    public function sendHeaders($headers)
    {
        $response = $this->_response;

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
        $response = $this->_response;

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
        $this->_response->end($content);
        return $this;
    }

    /**
     * @param string $file
     *
     * @return static
     */
    public function sendFile($file)
    {
        $this->_response->sendfile($this->alias->resolve($file));

        return $this;
    }
}