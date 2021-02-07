<?php

namespace ManaPHP\Ws\Server\Adapter;

use ArrayObject;
use ManaPHP\Aop\Unaspectable;
use ManaPHP\Component;
use ManaPHP\Coroutine\Context\Stickyable;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Ws\ServerInterface;
use Swoole\Coroutine;
use Swoole\Runtime;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use Throwable;

/**
 * @property-read \ManaPHP\Configuration\Configure $configure
 * @property-read \ManaPHP\Logging\LoggerInterface $logger
 * @property-read \ManaPHP\Http\RequestInterface   $request
 */
class Swoole extends Component implements ServerInterface, Unaspectable
{
    /**
     * @var string
     */
    protected $host = '0.0.0.0';

    /**
     * @var int
     */
    protected $port = 9501;

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * @var \Swoole\WebSocket\Server
     */
    protected $swoole;

    /**
     * @var \ManaPHP\Ws\Server\HandlerInterface
     */
    protected $handler;

    /**
     * @var ArrayObject[]
     */
    protected $contexts = [];

    /**
     * @var int
     */
    protected $worker_id;

    /**
     * @var array
     */
    protected $messageCoroutines = [];

    /**
     * @var array
     */
    protected $closeCoroutine = [];

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        $script_filename = get_included_files()[0];
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

        if (isset($options['host'])) {
            $this->host = $options['host'];
        }

        if (isset($options['port'])) {
            $this->port = (int)$options['port'];
        }

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

        unset($options['host'], $options['port']);
        $this->settings = $options ?: [];

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

    /**
     * @param \Swoole\Http\Request $request
     *
     * @return void
     */
    protected function prepareGlobals($request)
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

        $this->request->prepare($_get, [], $_server, null, $request->cookie ?? []);
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     *
     * @noinspection PhpUnusedParameterInspection
     *
     * @return void
     */
    public function onStart($server)
    {
        @cli_set_process_title(sprintf('manaphp %s: master', $this->configure->id));
    }

    /**
     * @return void
     */
    public function onManagerStart()
    {
        @cli_set_process_title(sprintf('manaphp %s: manager', $this->configure->id));
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param int                      $worker_id
     *
     * @return void
     */
    public function onWorkerStart($server, $worker_id)
    {
        $this->worker_id = $worker_id;

        @cli_set_process_title(sprintf('manaphp %s: worker/%d', $this->configure->id, $worker_id));

        try {
            $this->fireEvent('wsServer:start', compact('server', 'worker_id'));
        } catch (Throwable $throwable) {
            $this->logger->error($throwable);
        }
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param int                      $worker_id
     *
     * @return void
     */
    public function onWorkerStop($server, $worker_id)
    {
        try {
            $this->fireEvent('wsServer:stop', compact('server', 'worker_id'));
        } catch (Throwable $throwable) {
            $this->logger->error($throwable);
        }
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param \Swoole\Http\Request     $request
     *
     * @return void
     */
    public function onOpen(/** @noinspection PhpUnusedParameterInspection */ $server, $request)
    {
        $fd = $request->fd;

        try {
            $this->prepareGlobals($request);
            $globals = $this->request->getContext();
            $globals->_REQUEST['fd'] = $fd;

            $this->handler->onOpen($fd);
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

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param int                      $fd
     *
     * @return void
     */
    public function onClose($server, $fd)
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

        /** @var \ArrayObject $current_context */
        $current_context = Coroutine::getContext();
        foreach ($old_context as $k => $v) {
            $current_context[$k] = $v;
        }

        try {
            $this->handler->onClose($fd);
        } finally {
            null;
        }
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param Frame                    $frame
     *
     * @return void
     */
    public function onMessage(/** @noinspection PhpUnusedParameterInspection */ $server, $frame)
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

        try {
            $this->handler->onMessage($frame->fd, $frame->data);
        } catch (Throwable $throwable) {
            $this->logger->warn($throwable);
        }

        foreach ($current_context as $k => $v) {
            if ($v instanceof Stickyable && !isset($old_context[$k])) {
                $old_context[$k] = $v;
            }
        }
    }

    /**
     * @param \ManaPHP\Ws\Server\HandlerInterface $handler
     *
     * @return void
     */
    public function start($handler)
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            Runtime::enableCoroutine(true);
        }

        $this->handler = $handler;

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
    public function push($fd, $data)
    {
        return @$this->swoole->push($fd, is_string($data) ? $data : json_stringify($data));
    }

    /**
     * @param string $data
     *
     * @return void
     */
    public function broadcast($data)
    {
        $swoole = $this->swoole;

        foreach ($swoole->connections as $connection) {
            if ($swoole->isEstablished($connection)) {
                $swoole->push($connection, $data);
            }
        }
    }

    /**
     * @param int $fd
     *
     * @return bool
     */
    public function disconnect($fd)
    {
        return $this->swoole->disconnect($fd, 1000, '');
    }

    /**
     * @param int $fd
     *
     * @return bool
     */
    public function exists($fd)
    {
        return $this->swoole->exist($fd);
    }

    public function reload()
    {
        $this->swoole->reload();
    }

    /**
     * @return int
     */
    public function getWorkerId()
    {
        return $this->worker_id;
    }
}