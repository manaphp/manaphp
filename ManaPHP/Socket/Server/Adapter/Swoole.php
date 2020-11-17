<?php

namespace ManaPHP\Socket\Server\Adapter;

use ArrayObject;
use ManaPHP\Aop\Unaspectable;
use ManaPHP\Component;
use ManaPHP\Coroutine\Context\Stickyable;
use ManaPHP\Socket\ServerInterface;
use Swoole\Coroutine;
use Swoole\Runtime;
use Swoole\Server;
use Throwable;

class Swoole extends Component implements ServerInterface, Unaspectable
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
     * @var \Swoole\Server
     */
    protected $_swoole;

    /**
     * @var \ManaPHP\Socket\Server\HandlerInterface
     */
    protected $_handler;

    /**
     * @var array
     */
    protected $_contexts = [];

    /**
     * @var array
     */
    protected $_coroutines = [];

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
            'SERVER_ADDR'     => $this->_host,
            'SERVER_SOFTWARE' => 'Swoole/' . SWOOLE_VERSION . ' (' . PHP_OS . ') PHP/' . PHP_VERSION,
        ];

        unset($_GET, $_POST, $_REQUEST, $_FILES, $_COOKIE);

        if (isset($options['host'])) {
            $this->_host = $options['host'];
        }

        if (isset($options['port'])) {
            $this->_port = (int)$options['port'];
        }

        if (!isset($options['dispatch_mode'])) {
            $options['dispatch_mode'] = 2;
        }

        unset($options['host'], $options['port']);
        $this->_settings = $options ?: [];

        $this->_swoole = new Server($this->_host, $this->_port, $this->_settings['dispatch_mode']);

        $this->_swoole->on('Start', [$this, 'onStart']);
        $this->_swoole->on('ManagerStart', [$this, 'onManagerStart']);
        $this->_swoole->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->_swoole->on('Connect', [$this, 'onConnect']);
        $this->_swoole->on('Receive', [$this, 'onReceive']);
        $this->_swoole->on('Close', [$this, 'onClose']);
    }

    public function log($level, $message)
    {
        echo sprintf('[%s][%s]: ', date('c'), $level), $message, PHP_EOL;
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function onStart($server)
    {
        @cli_set_process_title(sprintf('manaphp %s: master', $this->configure->id));
    }

    public function onManagerStart()
    {
        @cli_set_process_title(sprintf('manaphp %s: manager', $this->configure->id));
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param int                      $worker_id
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function onWorkerStart($server, $worker_id)
    {
        @cli_set_process_title(sprintf('manaphp %s: worker/%d', $this->configure->id, $worker_id));
    }

    public function onConnect($server, $fd)
    {
        try {
            $this->_handler->onConnect($fd);
        } finally {
            null;
        }

        $context = new ArrayObject();
        foreach (Coroutine::getContext() as $k => $v) {
            if ($v instanceof Stickyable) {
                $context[$k] = $v;
            }
        }

        $this->_contexts[$fd] = $context;

        foreach ($this->_coroutines[$fd] ?? [] as $cid) {
            Coroutine::resume($cid);
        }
        unset($this->_coroutines[$fd]);
    }

    /**
     * @param int $fd
     */
    protected function _saveContext($fd)
    {
        $old_context = $this->_contexts[$fd];

        $current_context = Coroutine::getContext();
        foreach ($current_context as $k => $v) {
            if ($v instanceof Stickyable && !isset($old_context[$k])) {
                $old_context[$k] = $v;
            }
        }
    }

    protected function _restoreContext($fd)
    {
        if (!$old_context = $this->_contexts[$fd] ?? false) {
            $this->_coroutines[$fd][] = Coroutine::getCid();
            Coroutine::suspend();
            $old_context = $this->_contexts[$fd];
        }

        /** @var \ArrayObject $current_context */
        $current_context = Coroutine::getContext();
        foreach ($old_context as $k => $v) {
            $current_context[$k] = $v;
        }
    }

    public function onReceive($server, $fd, $from_id, $data)
    {
        $this->_restoreContext($fd);
        try {
            $this->_handler->onReceive($fd, $data);
        } catch (Throwable $throwable) {
            $this->logger->warn($throwable);
        }

        $this->_saveContext($fd);
    }

    public function onClose($server, $fd)
    {
        $this->_restoreContext($fd);
        try {
            $this->_handler->onClose($fd);
        } catch (Throwable $throwable) {
            $this->logger->warn($throwable);
        }

        unset($this->_contexts[$fd]);
    }

    /**
     * @param \ManaPHP\Socket\Server\HandlerInterface $handler
     */
    public function start($handler)
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            Runtime::enableCoroutine(true);
        }

        $this->_handler = $handler;

        echo PHP_EOL, str_repeat('+', 80), PHP_EOL;

        $settings = json_stringify($this->_settings);
        $this->log('info', sprintf('listen on: %s:%d with setting: %s', $this->_host, $this->_port, $settings));
        $this->_swoole->start();

        echo sprintf('[%s][info]: shutdown', date('c')), PHP_EOL;
    }

    /**
     * @param int $fd
     *
     * @return array
     */
    public function getClientInfo($fd)
    {
        return $this->_swoole->getClientInfo($fd);
    }

    /**
     * @param int    $fd
     * @param string $data
     *
     * @return mixed
     */
    public function send($fd, $data)
    {
        return $this->_swoole->send($fd, $data);
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
        return $this->_swoole->sendfile($fd, $filename, $offset, $length);
    }

    /**
     * @param int  $fd
     * @param bool $reset
     *
     * @return bool
     */
    public function close($fd, $reset = false)
    {
        return $this->_swoole->close($fd, $reset);
    }
}