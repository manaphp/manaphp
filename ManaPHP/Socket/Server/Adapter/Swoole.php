<?php

namespace ManaPHP\Socket\Server\Adapter;

use ArrayObject;
use ManaPHP\Component;
use ManaPHP\Coroutine\Context\Stickyable;
use ManaPHP\Socket\ServerInterface;
use Swoole\Coroutine;
use Swoole\Runtime;
use Swoole\Server;
use Throwable;

/**
 * @property-read \ManaPHP\Configuration\Configure $configure
 * @property-read \ManaPHP\AliasInterface          $alias
 * @property-read \ManaPHP\Logging\LoggerInterface $logger
 */
class Swoole extends Component implements ServerInterface
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
     * @var \Swoole\Server
     */
    protected $swoole;

    /**
     * @var \ManaPHP\Socket\Server\HandlerInterface
     */
    protected $handler;

    /**
     * @var array
     */
    protected $contexts = [];

    /**
     * @var array
     */
    protected $coroutines = [];

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
        ];

        unset($_GET, $_POST, $_REQUEST, $_FILES, $_COOKIE);

        if (isset($options['host'])) {
            $this->host = $options['host'];
        }

        if (isset($options['port'])) {
            $this->port = (int)$options['port'];
        }

        if (!isset($options['dispatch_mode'])) {
            $options['dispatch_mode'] = 2;
        }

        unset($options['host'], $options['port']);
        $this->settings = $options ?: [];

        $this->swoole = new Server($this->host, $this->port, $this->settings['dispatch_mode']);

        $this->swoole->on('Start', [$this, 'onStart']);
        $this->swoole->on('ManagerStart', [$this, 'onManagerStart']);
        $this->swoole->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->swoole->on('Connect', [$this, 'onConnect']);
        $this->swoole->on('Receive', [$this, 'onReceive']);
        $this->swoole->on('Close', [$this, 'onClose']);
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
     * @noinspection PhpUnusedParameterInspection
     *
     * @return void
     */
    public function onWorkerStart($server, $worker_id)
    {
        @cli_set_process_title(sprintf('manaphp %s: worker/%d', $this->configure->id, $worker_id));
    }

    /**
     * @param \Swoole\Server $server
     * @param int            $fd
     *
     * @return void
     */
    public function onConnect($server, $fd)
    {
        try {
            $this->handler->onConnect($fd);
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

        foreach ($this->coroutines[$fd] ?? [] as $cid) {
            Coroutine::resume($cid);
        }
        unset($this->coroutines[$fd]);
    }

    /**
     * @param int $fd
     *
     * @return void
     */
    protected function saveContext($fd)
    {
        $old_context = $this->contexts[$fd];

        $current_context = Coroutine::getContext();
        foreach ($current_context as $k => $v) {
            if ($v instanceof Stickyable && !isset($old_context[$k])) {
                $old_context[$k] = $v;
            }
        }
    }

    /**
     * @param int $fd
     *
     * @return void
     */
    protected function restoreContext($fd)
    {
        if (!$old_context = $this->contexts[$fd] ?? false) {
            $this->coroutines[$fd][] = Coroutine::getCid();
            Coroutine::suspend();
            $old_context = $this->contexts[$fd];
        }

        /** @var \ArrayObject $current_context */
        $current_context = Coroutine::getContext();
        foreach ($old_context as $k => $v) {
            $current_context[$k] = $v;
        }
    }

    /**
     * @param \Swoole\Server $server
     * @param int            $fd
     * @param int            $from_id
     * @param string         $data
     *
     * @return void
     */
    public function onReceive($server, $fd, $from_id, $data)
    {
        $this->restoreContext($fd);
        try {
            $this->handler->onReceive($fd, $data);
        } catch (Throwable $throwable) {
            $this->logger->warn($throwable);
        }

        $this->saveContext($fd);
    }

    /**
     * @param \Swoole\Server $server
     * @param int            $fd
     *
     * @return void
     */
    public function onClose($server, $fd)
    {
        $this->restoreContext($fd);
        try {
            $this->handler->onClose($fd);
        } catch (Throwable $throwable) {
            $this->logger->warn($throwable);
        }

        unset($this->contexts[$fd]);
    }

    /**
     * @param \ManaPHP\Socket\Server\HandlerInterface $handler
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
     * @param int $fd
     *
     * @return array
     */
    public function getClientInfo($fd)
    {
        return $this->swoole->getClientInfo($fd);
    }

    /**
     * @param int    $fd
     * @param string $data
     *
     * @return mixed
     */
    public function send($fd, $data)
    {
        return $this->swoole->send($fd, $data);
    }

    /**
     * @param int    $fd
     * @param string $filename
     * @param int    $offset
     * @param int    $length
     *
     * @return mixed
     */
    public function sendFile($fd, $filename, $offset = 0, $length = 0)
    {
        return $this->swoole->sendfile($fd, $filename, $offset, $length);
    }

    /**
     * @param int  $fd
     * @param bool $reset
     *
     * @return bool
     */
    public function close($fd, $reset = false)
    {
        return $this->swoole->close($fd, $reset);
    }
}