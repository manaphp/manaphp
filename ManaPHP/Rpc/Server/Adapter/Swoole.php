<?php
namespace ManaPHP\Rpc\Server\Adapter;

use ManaPHP\Component;
use ManaPHP\Coroutine\Context\Inseparable;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Rpc\ServerInterface;
use Swoole\Coroutine;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use Throwable;

class SwooleContext
{
    public $request;
    public $response;
    public $frame;
}

/**
 * Class Server
 * @package ManaPHP\WebSocket
 * @property-read \ManaPHP\Http\RequestInterface            $request
 * @property-read \ManaPHP\Http\ResponseInterface           $response
 * @property-read \ManaPHP\Rpc\Server\Adapter\SwooleContext $_context
 */
class Swoole extends Component implements ServerInterface
{
    /**
     * @var string
     */
    protected $_host = '0.0.0.0';

    /**
     * @var int
     */
    protected $_port = 8300;

    /**
     * @var array
     */
    protected $_settings = [];

    /**
     * @var \Swoole\WebSocket\Server
     */
    protected $_swoole;

    /**
     * @var \ManaPHP\Rpc\Server\HandlerInterface
     */
    protected $_handler;

    /**
     * @var array
     */
    protected $_contexts = [];

    /**
     * Server constructor.
     *
     * @param array $options
     */
    public function __construct($options = null)
    {
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

        if (isset($options['host'])) {
            $this->_host = $options['host'];
        }

        if (isset($options['port'])) {
            $this->_port = (int)$options['port'];
        }

        if (isset($options['max_request']) && $options['max_request'] < 1) {
            $options['max_request'] = 1;
        }

        $this->_settings = $options ?: [];

        $this->_swoole = new Server($this->_host, $this->_port);
        $this->_swoole->set($this->_settings);

        $this->_swoole->on('open', [$this, 'onOpen']);
        $this->_swoole->on('close', [$this, 'onClose']);
        $this->_swoole->on('message', [$this, 'onMessage']);
        $this->_swoole->on('request', [$this, 'onRequest']);
    }

    public function log($level, $message)
    {
        echo sprintf('[%s][%s]: ', date('c'), $level), $message, PHP_EOL;
    }

    /**
     * @param \Swoole\Http\Request $request
     */
    protected function _prepareGlobals($request)
    {
        $_server = array_change_key_case($request->server, CASE_UPPER);
        unset($_server['SERVER_SOFTWARE']);

        foreach ($request->header ?: [] as $k => $v) {
            if (in_array($k, ['content-type', 'content-length'], true)) {
                $_server[strtoupper(strtr($k, '-', '_'))] = $v;
            } else {
                $_server['HTTP_' . strtoupper(strtr($k, '-', '_'))] = $v;
            }
        }

        $_server += $_SERVER;

        $_get = $request->get ?: [];
        $request_uri = $_server['REQUEST_URI'];
        $_get['_url'] = ($pos = strpos($request_uri, '?')) ? substr($request_uri, 0, $pos) : $request_uri;

        $globals = $this->request->getGlobals();

        $globals->_GET = $_get;
        $globals->_REQUEST = $_get;
        $globals->_SERVER = $_server;
        $globals->_COOKIE = $request->cookie ?: [];
    }

    /**
     * @param \Swoole\Http\Request  $request
     * @param \Swoole\Http\Response $response
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

            $this->_handler->handle();
        } catch (Throwable $exception) {
            $str = date('c') . ' ' . get_class($exception) . ': ' . $exception->getMessage() . PHP_EOL;
            $str .= '    at ' . $exception->getFile() . ':' . $exception->getLine() . PHP_EOL;
            $traces = $exception->getTraceAsString();
            $str .= preg_replace('/#\d+\s/', '    at ', $traces);
            echo $str . PHP_EOL;
        }

        if (!MANAPHP_COROUTINE_ENABLED) {
            global $__root_context;

            if ($__root_context !== null) {
                foreach ($__root_context as $owner) {
                    unset($owner->_context);
                }

                $__root_context = null;
            }
        }
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param \Swoole\Http\Request     $req
     */
    public function onOpen($server, $req)
    {
        try {
            $fd = $req->fd;
            $this->_prepareGlobals($req);
        } finally {
            /** @noinspection PhpUndefinedMethodInspection */
            $this->_contexts[$fd] = $context = Coroutine::getContext();
            foreach ($context as $k => $v) {
                if ($v instanceof Inseparable) {
                    unset($k);
                }
            }
        }
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param int                      $fd
     */
    public function onClose($server, $fd)
    {
        /** @var  \Swoole\WebSocket\Server $server */
        if (!$server->isEstablished($fd)) {
            return;
        }

        while (!isset($this->_contexts[$fd])) {
            Coroutine::sleep(0.01);
            $this->log('info', 'open is not ready');
        }

        try {
            /** @var \ArrayObject $context */
            /** @noinspection PhpUndefinedMethodInspection */
            $context = Coroutine::getContext();

            foreach ($this->_contexts[$fd] as $k => $v) {
                /** @noinspection OnlyWritesOnParameterInspection */
                $context[$k] = $v;
            }
            $this->_handler->onClose($fd);
        } finally {
            unset($this->_contexts[$fd]);
        }
    }

    public function message($data)
    {
        if (!$json = json_decode($data, true)) {
            return ['jsonrpc' => '2.0', 'error' => ['code' => '-32700', 'message' => 'Parse error'], 'id' => null];
        }

        if (!isset($json['jsonrpc'], $json['method'], $json['params'], $json['id']) || $json['jsonrpc'] !== '2.0' || !is_array($json['params'])) {
            return ['jsonrpc' => '2.0', 'error' => ['code' => '-32600', 'message' => 'Invalid Request'], 'id' => null];
        }

        $globals = $this->request->getGlobals();
        $globals->_POST = $json['params'];
        $globals->_REQUEST = $globals->_GET + $globals->_POST;
        $response = $this->_handler->handle();
        if (!isset($response['code'], $response['message'])) {
            return ['jsonrpc' => '2.0', 'error' => ['code' => '-32603', 'message' => 'Internal error'], 'id' => null];
        }
        if (isset($response['data'])) {
            return ['jsonrpc' => '2.0', 'result' => $response['data'], 'id' => $json['id']];
        } else {
            return ['jsonrpc' => '2.0', 'error' => $response, 'id' => $json['id']];
        }
    }

    /**
     * @param \ManaPHP\Http\ResponseContext $response
     */
    public function send($response)
    {
        $this->eventsManager->fireEvent('response:beforeSend', $this, $response);

        $sw_response = $this->_context->response;

        $sw_response->status($response->status_code);

        foreach ($response->headers as $name => $value) {
            $sw_response->header($name, $value, false);
        }

        $server = $this->request->getGlobals()->_SERVER;

        $sw_response->header('X-Request-Id', $this->request->getRequestId(), false);
        $sw_response->header('X-Response-Time', sprintf('%.3f', microtime(true) - $server['REQUEST_TIME_FLOAT']), false);

        if ($response->cookies) {
            throw new NotSupportedException('rpc not support cookie send');
        }

        if ($response->file) {
            throw new NotSupportedException('rpc not support send file');
        }

        $content = $response->content;

        if (is_string($content)) {
            $sw_response->end($content);
        } else {
            $sw_response->end(json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        }

        $this->eventsManager->fireEvent('response:afterSend', $this, $response);
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param Frame                    $frame
     */
    public function onMessage($server, $frame)
    {
        $fd = $frame->fd;
        $this->_context->frame = $frame;

        try {
            while (!isset($this->_contexts[$fd])) {
                Coroutine::sleep(0.01);
                $this->log('info', 'open is not ready');
            }

            /** @var \ArrayObject $context */
            /** @noinspection PhpUndefinedMethodInspection */
            $context = Coroutine::getContext();

            foreach ($this->_contexts[$fd] as $k => $v) {
                /** @noinspection OnlyWritesOnParameterInspection */
                $context[$k] = $v;
            }
            $this->_handler->onMessage($fd, $frame->data);
            $response = $this->message($frame->data);
        } catch (Throwable $throwable) {
            $response = ['jsonrpc' => '2.0', 'error' => ['code' => -32603, 'message' => 'Internal error'], 'id' => null];
            $this->logger->warn($throwable);
        }

        $server->push($frame->fd, json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), WEBSOCKET_OPCODE_BINARY);
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
            sprintf('starting listen on: %s:%d with setting: %s', $this->_host, $this->_port, json_encode($this->_settings, JSON_UNESCAPED_SLASHES)));
        $this->_swoole->start();

        echo sprintf('[%s][info]: shutdown', date('c')), PHP_EOL;
    }
}