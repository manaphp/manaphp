<?php
namespace ManaPHP\Rpc\Server\Adapter;

use ManaPHP\Component;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Rpc\ServerInterface;
use Swoole\WebSocket\Server;
use Throwable;

class SwooleContext
{
    public $response;
    public $frame;
}

/**
 * Class Server
 * @package ManaPHP\WebSocket
 * @property-read \ManaPHP\Http\RequestInterface              $request
 * @property-read \ManaPHP\Http\Response                      $response
 * @property-read \ManaPHP\Rpc\Server\Adapter\SwooleContext   $_context
 * @property-read \ManaPHP\Coroutine\Context\ManagerInterface $contextManager
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

        $options['enable_coroutine'] = MANAPHP_COROUTINE_ENABLED ? 1 : 0;
        $this->_settings = $options ?: [];

        $this->_swoole = new Server($this->_host, $this->_port);
        $this->_swoole->set($this->_settings);

        $this->_swoole->on('request', [$this, 'onRequest']);

        $this->_swoole->on('open', [$this, 'onOpen']);
        $this->_swoole->on('close', [$this, 'onClose']);
        $this->_swoole->on('message', [$this, 'onMessage']);
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
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param \Swoole\Http\Request     $request
     */
    public function onOpen($server, $request)
    {
        if (isset($request->header['upgrade'])) {
            $this->_prepareGlobals($request);
            $this->contextManager->save($request->fd);
        }
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param int                      $fd
     */
    public function onClose($server, $fd)
    {
        if ($server->isEstablished($fd)) {
            $this->contextManager->delete($fd);
        }
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function message($data)
    {
        if (!$data) {
            return ['code' => -32700, 'message' => 'Parse error'];
        }

        if (!isset($data['jsonrpc'], $data['method'], $data['params'], $data['id']) || $data['jsonrpc'] !== '2.0' || !is_array($data['params'])) {
            return ['code' => -32600, 'message' => 'Invalid Request'];
        }

        $globals = $this->request->getGlobals();
        $globals->_POST = $data['params'];
        $globals->_REQUEST = $globals->_GET + $globals->_POST;
        $response = $this->_handler->handle();
        if (!isset($response['code'], $response['message'])) {
            return ['code' => -32603, 'message' => 'Internal error'];
        } else {
            return $response;
        }
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param \Swoole\WebSocket\Frame  $frame
     */
    public function onMessage($server, $frame)
    {
        $this->_context->frame = $frame;
        $this->contextManager->restore($frame->fd);

        $response = $this->response->_context;
        $json = json_decode($frame->data, true);
        try {
            $content = $this->message($json);
            if (is_string($content)) {
                if ($content === '') {
                    $content = [];
                } else {
                    $content = json_decode($content, true);
                }
            }
        } catch (Throwable $throwable) {
            $content = ['code' => -32603, 'message' => 'Internal error'];
            $this->logger->warn($throwable);
        }

        if ($content['code'] === 0) {
            $response->content = ['jsonrpc' => '2.0', 'result' => $content, 'id' => $json['id'] ?? null];
        } else {
            $response->content = ['jsonrpc' => '2.0', 'error' => $content, 'id' => $json['id'] ?? null];
        }

        $this->send($response);
    }

    /**
     * @param \ManaPHP\Http\ResponseContext $response
     */
    public function send($response)
    {
        $this->eventsManager->fireEvent('response:beforeSend', $this, $response);

        $context = $this->_context;
        if ($context->frame) {
            $server = $this->request->getGlobals()->_SERVER;
            $response->content[isset($response->content['result']) ? 'result' : 'error']['headers'] = [
                'X-Request-Id' => $this->request->getRequestId(),
                'X-Response-Time' => sprintf('%.3f', microtime(true) - $server['REQUEST_TIME_FLOAT'])
            ];

            $this->_swoole->push($context->frame->fd, json_encode($response->content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
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
        }

        $this->eventsManager->fireEvent('response:afterSend', $this, $response);
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