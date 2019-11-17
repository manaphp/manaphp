<?php
namespace ManaPHP\Rpc\Server\Adapter;

use ArrayObject;
use ManaPHP\Coroutine\Context\Stickyable;
use ManaPHP\Exception\NotSupportedException;
use Swoole\Coroutine;
use Swoole\WebSocket\Server;
use Throwable;

class SwooleContext
{
    /**
     * @var int
     */
    public $fd;

    /**
     * @var \Swoole\Http\Response
     */
    public $response;
}

/**
 * Class Server
 * @package ManaPHP\WebSocket
 * @property-read \ManaPHP\Rpc\Server\Adapter\SwooleContext $_context
 */
class Swoole extends \ManaPHP\Rpc\Server
{
    /**
     * @var array
     */
    protected $_settings = [];

    /**
     * @var \Swoole\WebSocket\Server
     */
    protected $_swoole;

    /**
     * @var ArrayObject[]
     */
    protected $_contexts = [];

    /**
     * Server constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        $script_filename = get_included_files()[0];
        $_SERVER = [
            'DOCUMENT_ROOT' => dirname($script_filename),
            'SCRIPT_FILENAME' => $script_filename,
            'SCRIPT_NAME' => '/' . basename($script_filename),
            'SERVER_ADDR' => $this->_host,
            'SERVER_SOFTWARE' => 'Swoole/' . SWOOLE_VERSION . ' PHP/' . PHP_VERSION,
            'PHP_SELF' => '/' . basename($script_filename),
            'QUERY_STRING' => '',
            'REQUEST_SCHEME' => 'http',
        ];

        unset($_GET, $_POST, $_REQUEST, $_FILES, $_COOKIE);

        if (isset($options['max_request']) && $options['max_request'] < 1) {
            $options['max_request'] = 1;
        }

        if (isset($options['dispatch_mode'])) {
            if (!in_array((int)$options['dispatch_mode'], [2, 4, 5], true)) {
                throw new NotSupportedException('only support dispatch_mode=2,4,5');
            }
        } else {
            $options['dispatch_mode'] = 2;
        }

        $options['enable_coroutine'] = MANAPHP_COROUTINE_ENABLED ? 1 : 0;
        $this->_settings = $options ?: [];

        $this->_swoole = new Server($this->_host, $this->_port);
        $this->_swoole->set($this->_settings);

        $this->_swoole->on('request', [$this, 'onRequest']);

        $this->_swoole->on('open', [$this, 'onOpen']);
        $this->_swoole->on('close', [$this, 'onClose']);
        $this->_swoole->on('message', [$this, 'onMessage']);
    }

    /**
     * @param \Swoole\Http\Request $request
     */
    protected function _prepareGlobals($request)
    {
        $_server = array_change_key_case($request->server, CASE_UPPER);
        foreach ($request->header ?: [] as $k => $v) {
            if (in_array($k, ['content-type', 'content-length'], true)) {
                $_server[strtoupper(strtr($k, '-', '_'))] = $v;
            } else {
                $_server['HTTP_' . strtoupper(strtr($k, '-', '_'))] = $v;
            }
        }
        $_server += $_SERVER;

        $_get = $request->get ?: [];
        $_server['WS_ENDPOINT'] = $_get['_url'] = rtrim($_server['REQUEST_URI'], '/');

        $_post = $request->post ?: [];
        if (!$_post && isset($_server['REQUEST_METHOD']) && !in_array($_server['REQUEST_METHOD'], ['GET', 'OPTIONS'], true)) {
            $data = $request->rawContent();

            if (isset($_server['CONTENT_TYPE']) && strpos($_server['CONTENT_TYPE'], 'application/json') !== false) {
                $_post = json_parse($data);
            } else {
                parse_str($data, $_post);
            }
            if (!is_array($_post)) {
                $_post = [];
            }
        }

        unset($_post['_url']);

        $globals = $this->request->getGlobals();

        $globals->_GET = $_get;
        $globals->_POST = $_post;
        /** @noinspection AdditionOperationOnArraysInspection */
        $globals->_REQUEST = $_post + $_get;
        $globals->_SERVER = $_server;
    }

    /**
     * @param \Swoole\Http\Request  $request
     * @param \Swoole\Http\Response $response
     */
    public function onRequest($request, $response)
    {
        if ($request->server['request_uri'] === '/favicon.ico') {
            $response->status(404);
            $response->end();
            return;
        }

        $this->_context->response = $response;

        try {
            $this->_prepareGlobals($request);

            if ($this->authenticate()) {
                $this->_handler->handle();
            } else {
                $this->send($this->response->getContext());
            }
        } catch (Throwable $throwable) {
            $str = date('c') . ' ' . get_class($throwable) . ': ' . $throwable->getMessage() . PHP_EOL;
            $str .= '    at ' . $throwable->getFile() . ':' . $throwable->getLine() . PHP_EOL;
            $str .= preg_replace('/#\d+\s/', '    at ', $throwable->getTraceAsString());
            echo $str . PHP_EOL;
        }
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param \Swoole\Http\Request     $request
     */
    public function onOpen($server, $request)
    {
        if (isset($request->header['upgrade'])) {
            $this->_prepareGlobals($request);

            $context = new ArrayObject();
            foreach (Coroutine::getContext() as $k => $v) {
                if ($v instanceof Stickyable) {
                    $context[$k] = $v;
                }
            }
            $this->_contexts[$request->fd] = $context;

            $this->request->setRequestId();

            $response = $this->response->getContext();
            if (!$this->authenticate()) {
                $this->_context->fd = $request->fd;
                $this->send($response);
                $server->close($request->fd);
            } elseif ($response->content) {
                $this->_context->fd = $request->fd;
                $this->send($response);
            }
        }
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param int                      $fd
     */
    public function onClose(/** @noinspection PhpUnusedParameterInspection */ $server, $fd)
    {
        unset($this->_contexts[$fd]);
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param \Swoole\WebSocket\Frame  $frame
     */
    public function onMessage(/** @noinspection PhpUnusedParameterInspection */ $server, $frame)
    {
        /** @var \ArrayObject $current_context */
        $current_context = Coroutine::getContext();
        $old_context = $this->_contexts[$frame->fd];
        foreach ($old_context as $k => $v) {
            /** @noinspection OnlyWritesOnParameterInspection */
            $current_context[$k] = $v;
        }

        $this->_context->fd = $frame->fd;

        $this->request->setRequestId();

        $response = $this->response->getContext();
        if (!$json = json_parse($frame->data)) {
            $response->content = ['code' => -32700, 'message' => 'Parse error'];
            $this->send($response);
        } elseif (!isset($json['jsonrpc'], $json['method'], $json['params'], $json['id'])
            || $json['jsonrpc'] !== '2.0' || !is_array($json['params'])) {
            $response->content = ['code' => -32600, 'message' => 'Invalid Request'];
            $this->send($response);
        } else {
            $globals = $this->request->getGlobals();
            $globals->_GET['_url'] = $globals->_SERVER['WS_ENDPOINT'] . '/' . $json['method'];
            $globals->_POST = $json['params'];
            /** @noinspection AdditionOperationOnArraysInspection */
            $globals->_REQUEST = $globals->_POST + $globals->_GET;
            $globals->_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
            $globals->_SERVER['REQUEST_TIME'] = (int)$globals->_SERVER['REQUEST_TIME_FLOAT'];
            try {
                $this->_handler->handle();
            } catch (Throwable $throwable) {
                $response->content = ['code' => -32603, 'message' => 'Internal error'];
                $this->logger->warn($throwable);
            }
        }

        foreach ($current_context as $k => $v) {
            if ($v instanceof Stickyable && !isset($old_context[$k])) {
                $old_context[$k] = $v;
            }
        }
    }

    /**
     * @param \ManaPHP\Http\ResponseContext $response
     */
    public function send($response)
    {
        $context = $this->_context;
        if ($context->fd) {
            $server = $this->request->getGlobals()->_SERVER;
            $headers = [
                'X-Request-Id' => $this->request->getRequestId(),
                'X-Response-Time' => sprintf('%.3f', microtime(true) - $server['REQUEST_TIME_FLOAT'])
            ];

            if ($response->content['code'] === 0) {
                $content = ['jsonrpc' => '2.0', 'result' => $response->content, 'id' => $json['id'] ?? null, 'headers' => $headers];
            } else {
                $content = ['jsonrpc' => '2.0', 'error' => $response->content, 'id' => $json['id'] ?? null, 'headers' => $headers];
            }
            $this->_swoole->push($context->fd, json_stringify($content));
        } else {
            $sw_response = $this->_context->response;

            $sw_response->status($response->status_code);

            foreach ($response->headers as $name => $value) {
                $sw_response->header($name, $value, false);
            }

            $server = $this->request->getGlobals()->_SERVER;

            $sw_response->header('X-Request-Id', $this->request->getRequestId(), false);
            $sw_response->header('X-Response-Time', sprintf('%.3f', microtime(true) - $server['REQUEST_TIME_FLOAT']), false);

            if ($response->cookies) {
                throw new NotSupportedException('rpc not support cookies');
            }

            if ($response->file) {
                throw new NotSupportedException('rpc not support send file');
            }

            $content = $response->content;
            $sw_response->end(is_string($content) ? $content : json_stringify($content));
        }
    }

    /**
     * @param \ManaPHP\Rpc\Server\HandlerInterface $handler
     *
     * @return void
     */
    public function start($handler)
    {
        $this->_handler = $handler;

        echo PHP_EOL, str_repeat('+', 80), PHP_EOL;

        $this->log('info',
            sprintf('starting listen on: %s:%d with setting: %s', $this->_host, $this->_port, json_stringify($this->_settings)));
        $this->_swoole->start();

        echo sprintf('[%s][info]: shutdown', date('c')), PHP_EOL;
    }
}