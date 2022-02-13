<?php
declare(strict_types=1);

namespace ManaPHP\Rpc\Http\Server\Adapter;

use ArrayObject;
use ManaPHP\Coroutine\Context\Stickyable;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Rpc\Http\AbstractServer;
use Swoole\Coroutine;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Runtime;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use Throwable;

/**
 * @property-read \ManaPHP\ConfigInterface                       $config
 * @property-read \ManaPHP\Logging\LoggerInterface               $logger
 * @property-read \ManaPHP\Rpc\Http\Server\Adapter\SwooleContext $context
 */
class Swoole extends AbstractServer
{
    protected array $settings = [];
    protected Server $swoole;

    /**
     * @var ArrayObject[]
     */
    protected array $contexts = [];

    protected array $messageCoroutines = [];
    protected array $closeCoroutine = [];

    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $script_filename = get_included_files()[0];
        /** @noinspection PhpArrayWriteIsNotUsedInspection */
        $_SERVER = [
            'DOCUMENT_ROOT'   => dirname($script_filename),
            'SCRIPT_FILENAME' => $script_filename,
            'SCRIPT_NAME'     => '/' . basename($script_filename),
            'SERVER_ADDR'     => $this->host,
            'SERVER_SOFTWARE' => 'Swoole/' . SWOOLE_VERSION . ' (' . PHP_OS . ') PHP/' . PHP_VERSION,
            'PHP_SELF'        => '/' . basename($script_filename),
            'QUERY_STRING'    => '',
            'REQUEST_SCHEME'  => 'http',
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

        unset($options['host'], $options['port']);
        $this->settings = $options ?: [];

        $this->swoole = new Server($this->host, $this->port);
        $this->swoole->set($this->settings);

        $this->swoole->on('Start', [$this, 'onStart']);
        $this->swoole->on('ManagerStart', [$this, 'onManagerStart']);
        $this->swoole->on('WorkerStart', [$this, 'onWorkerStart']);

        $this->swoole->on('request', [$this, 'onRequest']);

        $this->swoole->on('open', [$this, 'onOpen']);
        $this->swoole->on('close', [$this, 'onClose']);
        $this->swoole->on('message', [$this, 'onMessage']);
    }

    protected function prepareGlobals(Request $request): void
    {
        $_server = array_change_key_case($request->server, CASE_UPPER);
        foreach ($request->header ?: [] as $k => $v) {
            if (in_array($k, ['content-type', 'content-length'], true)) {
                $_server[strtoupper(strtr($k, '-', '_'))] = $v;
            } else {
                $_server['HTTP_' . strtoupper(strtr($k, '-', '_'))] = $v;
            }
        }
        $_server = array_merge($_SERVER, $_server);

        $_get = $request->get ?: [];
        $_server['WS_ENDPOINT'] = $_get['_url'] = rtrim($_server['REQUEST_URI'], '/');

        $_post = $request->post ?: [];

        $raw_body = $request->rawContent();
        $this->globals->prepare($_get, $_post, $_server, $raw_body);
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function onStart(Server $server): void
    {
        @cli_set_process_title(sprintf('manaphp %s: master', $this->config->get("id")));
    }

    public function onManagerStart(): void
    {
        @cli_set_process_title(sprintf('manaphp %s: manager', $this->config->get("id")));
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function onWorkerStart(Server $server, int $worker_id): void
    {
        @cli_set_process_title(sprintf('manaphp %s: worker/%d', $this->config->get("id"), $worker_id));
    }

    public function onRequest(Request $request, Response $response): void
    {
        if ($request->server['request_uri'] === '/favicon.ico') {
            $response->status(404);
            $response->end();
            return;
        }

        $this->context->response = $response;

        try {
            $this->prepareGlobals($request);

            if ($this->authenticate()) {
                $this->rpcHandler->handle();
            } else {
                $this->send();
            }
        } catch (Throwable $throwable) {
            $str = date('c') . ' ' . get_class($throwable) . ': ' . $throwable->getMessage() . PHP_EOL;
            $str .= '    at ' . $throwable->getFile() . ':' . $throwable->getLine() . PHP_EOL;
            $str .= preg_replace('/#\d+\s/', '    at ', $throwable->getTraceAsString());
            echo $str . PHP_EOL;
        }
    }

    public function onOpen(Server $server, Request $request): void
    {
        if (isset($request->header['upgrade'])) {
            $fd = $request->fd;

            $this->prepareGlobals($request);

            $this->request->setRequestId();

            if (!$this->authenticate()) {
                $this->context->fd = $fd;
                $this->send();
                $server->close($fd);
            } elseif ($this->response->hasContent()) {
                $this->context->fd = $fd;
                $this->send();
            }

            $context = new ArrayObject();
            foreach (Coroutine::getContext() as $k => $v) {
                if ($v instanceof Stickyable) {
                    $context[$k] = $v;
                }
            }

            $this->contexts[$fd] = $context;

            foreach ($this->messageCoroutines[$fd] ?? [] as $cid) {
                Coroutine::resume($cid);
            }
            unset($this->messageCoroutines[$fd]);

            if ($cid = $this->closeCoroutine[$fd] ?? false) {
                unset($this->closeCoroutine[$fd]);
                Coroutine::resume($cid);
            }
        }
    }

    public function onClose(Server $server, int $fd): void
    {
        if (!$server->isEstablished($fd)) {
            return;
        }

        if (!isset($this->contexts[$fd])) {
            $this->closeCoroutine[$fd] = Coroutine::getCid();
            Coroutine::suspend();
        }
        unset($this->contexts[$fd]);
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function onMessage(Server $server, Frame $frame): void
    {
        $fd = $frame->fd;

        if (!$old_context = $this->contexts[$fd] ?? false) {
            $this->messageCoroutines[$fd][] = Coroutine::getCid();
            Coroutine::suspend();
            $old_context = $this->contexts[$fd];
        }

        /** @var \ArrayObject $current_context */
        $current_context = Coroutine::getContext();
        foreach ($old_context as $k => $v) {
            $current_context[$k] = $v;
        }

        $this->context->fd = $fd;

        $this->request->setRequestId();

        if (!$json = json_parse($frame->data)) {
            $this->response->setContent(['code' => -32700, 'message' => 'Parse error']);
            $this->send();
        } elseif (!isset($json['jsonrpc'], $json['method'], $json['params'], $json['id'])
            || $json['jsonrpc'] !== '2.0'
            || !is_array($json['params'])
        ) {
            $this->response->setContent(['code' => -32600, 'message' => 'Invalid Request']);
            $this->send();
        } else {
            $globals = $this->globals->get();
            $globals->_GET['_url'] = $globals->_SERVER['WS_ENDPOINT'] . '/' . $json['method'];
            $globals->_POST = $json['params'];
            $globals->_REQUEST = $globals->_POST + $globals->_GET;
            $globals->_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
            $globals->_SERVER['REQUEST_TIME'] = (int)$globals->_SERVER['REQUEST_TIME_FLOAT'];
            try {
                $this->rpcHandler->handle();
            } catch (Throwable $throwable) {
                $this->response->setContent(['code' => -32603, 'message' => 'Internal error']);
                $this->logger->warning($throwable);
            }
        }

        foreach ($current_context as $k => $v) {
            if ($v instanceof Stickyable && !isset($old_context[$k])) {
                $old_context[$k] = $v;
            }
        }
    }

    public function send(): void
    {
        $context = $this->context;
        if ($context->fd) {
            $headers = [
                'X-Request-Id'    => $this->request->getRequestId(),
                'X-Response-Time' => $this->request->getElapsedTime()
            ];

            $id = $json['id'] ?? null;
            $content = $this->response->getContent();
            if ($content['code'] === 0) {
                $data = ['jsonrpc' => '2.0', 'result' => $content, 'id' => $id, 'headers' => $headers];
            } else {
                $data = ['jsonrpc' => '2.0', 'error' => $content, 'id' => $id, 'headers' => $headers];
            }
            $this->swoole->push($context->fd, json_stringify($data));
        } else {
            $response = $this->context->response;

            $response->status($this->response->getStatusCode());

            foreach ($this->response->getHeaders() as $name => $value) {
                $response->header($name, $value, false);
            }

            $response->header('X-Request-Id', $this->request->getRequestId(), false);
            $response->header('X-Response-Time', $this->request->getElapsedTime(), false);

            if ($this->response->hasFile()) {
                throw new NotSupportedException('rpc not support send file');
            }

            $data = $this->response->getContent();
            $response->end(is_string($data) ? $data : json_stringify($data));
        }
    }

    public function start(): void
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            Runtime::enableCoroutine(true);
        }

        echo PHP_EOL, str_repeat('+', 80), PHP_EOL;

        $settings = json_stringify($this->settings);
        console_log('info', ['listen on: %s:%d with setting: %s', $this->host, $this->port, $settings]);
        $this->swoole->start();
        console_log('info', 'shutdown');
    }
}
