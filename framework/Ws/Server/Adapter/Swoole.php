<?php
declare(strict_types=1);

namespace ManaPHP\Ws\Server\Adapter;

use ArrayObject;
use ManaPHP\ConfigInterface;
use ManaPHP\Coroutine\Context\Stickyable;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Http\GlobalsInterface;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Ws\HandlerInterface;
use ManaPHP\Ws\Server\Event\ServerStart;
use ManaPHP\Ws\Server\Event\ServerStop;
use ManaPHP\Ws\ServerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Swoole\Coroutine;
use Swoole\Http\Request;
use Swoole\Runtime;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use Throwable;

class Swoole implements ServerInterface
{
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected ConfigInterface $config;
    #[Autowired] protected LoggerInterface $logger;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected GlobalsInterface $globals;
    #[Autowired] protected HandlerInterface $wsHandler;

    #[Autowired] protected string $host = '0.0.0.0';
    #[Autowired] protected int $port = 9501;
    #[Autowired] protected array $settings = [];

    protected Server $swoole;

    /**
     * @var ArrayObject[]
     */
    protected array $contexts = [];
    protected int $worker_id;
    protected array $messageCoroutines = [];
    protected array $closeCoroutine = [];

    public function __construct()
    {
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

        if (isset($this->settings['max_request']) && $this->settings['max_request'] < 1) {
            $this->settings['max_request'] = 1;
        }

        if (isset($this->settings['dispatch_mode'])) {
            if (!in_array((int)$this->settings['dispatch_mode'], [2, 4, 5], true)) {
                throw new NotSupportedException('only support dispatch_mode=2,4,5');
            }
        } else {
            $this->settings['dispatch_mode'] = 2;
        }

        $this->swoole = new Server($this->host, $this->port);
        $this->swoole->set($this->settings);

        $this->swoole->on('Start', [$this, 'onStart']);
        $this->swoole->on('ManagerStart', [$this, 'onManagerStart']);
        $this->swoole->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->swoole->on('WorkerStop', [$this, 'onWorkerStop']);
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

        $this->globals->prepare($_get, [], $_server, null, $request->cookie ?? []);
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

    public function onWorkerStart(Server $server, int $worker_id): void
    {
        $this->worker_id = $worker_id;

        @cli_set_process_title(sprintf('manaphp %s: worker/%d', $this->config->get("id"), $worker_id));

        try {
            $this->eventDispatcher->dispatch(new ServerStart($this, $server, $worker_id));
        } catch (Throwable $throwable) {
            $this->logger->error('', ['exception' => $throwable]);
        }
    }

    public function onWorkerStop(Server $server, int $worker_id): void
    {
        try {
            $this->eventDispatcher->dispatch(new ServerStop($this, $server, $worker_id));
        } catch (Throwable $throwable) {
            $this->logger->error('', ['exception' => $throwable]);
        }
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function onOpen(Server $server, Request $request): void
    {
        $fd = $request->fd;

        try {
            $this->prepareGlobals($request);
            $this->request->set('fd', $fd);
            $this->wsHandler->onOpen($fd);
        } finally {
            null;
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

    public function onClose(Server $server, int $fd): void
    {
        if (!$server->isEstablished($fd)) {
            return;
        }

        if (!$old_context = $this->contexts[$fd] ?? false) {
            $this->closeCoroutine[$fd] = Coroutine::getCid();
            Coroutine::suspend();
            $old_context = $this->contexts[$fd];
        }
        unset($this->contexts[$fd]);

        /** @var ArrayObject $current_context */
        $current_context = Coroutine::getContext();
        foreach ($old_context as $k => $v) {
            $current_context->$k = $v;
        }

        try {
            $this->wsHandler->onClose($fd);
        } finally {
            null;
        }
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

        /** @var ArrayObject $current_context */
        $current_context = Coroutine::getContext();
        foreach ($old_context as $k => $v) {
            $current_context[$k] = $v;
        }

        try {
            $this->wsHandler->onMessage($frame->fd, $frame->data);
        } catch (Throwable $throwable) {
            $this->logger->warning('', ['exception' => $throwable]);
        }

        foreach ($current_context as $k => $v) {
            if ($v instanceof Stickyable && !isset($old_context[$k])) {
                $old_context[$k] = $v;
            }
        }
    }

    public function start(): void
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            Runtime::enableCoroutine();
        }

        echo PHP_EOL, str_repeat('+', 80), PHP_EOL;

        $settings = json_stringify($this->settings);
        console_log('info', ['listen on: %s:%d with setting: %s', $this->host, $this->port, $settings]);
        $this->swoole->start();
        console_log('info', 'shutdown');
    }

    /**
     * @param int   $fd
     * @param mixed $data
     *
     * @return bool
     */
    public function push(int $fd, mixed $data): bool
    {
        return @$this->swoole->push($fd, is_string($data) ? $data : json_stringify($data));
    }

    /**
     * @param string $data
     *
     * @return void
     */
    public function broadcast(string $data): void
    {
        $swoole = $this->swoole;

        foreach ($swoole->connections as $connection) {
            if ($swoole->isEstablished($connection)) {
                $swoole->push($connection, $data);
            }
        }
    }

    public function disconnect(int $fd): bool
    {
        return $this->swoole->disconnect($fd, 1000);
    }

    public function exists(int $fd): bool
    {
        return $this->swoole->exist($fd);
    }

    public function reload(): void
    {
        $this->swoole->reload();
    }

    public function getWorkerId(): int
    {
        return $this->worker_id;
    }
}