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
 * Class Server
 *
 * @package ManaPHP\Ws
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
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
     * @var \Swoole\WebSocket\Server
     */
    protected $_swoole;

    /**
     * @var \ManaPHP\Ws\Server\HandlerInterface
     */
    protected $_handler;

    /**
     * @var ArrayObject[]
     */
    protected $_contexts = [];

    /**
     * @var array
     */
    protected $_messageCoroutines = [];

    /**
     * @var array
     */
    protected $_closeCoroutine = [];

    /**
     * Server constructor.
     *
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
            'PHP_SELF'        => '/' . basename($script_filename),
            'QUERY_STRING'    => '',
            'REQUEST_SCHEME'  => 'http',
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

        if (isset($options['dispatch_mode'])) {
            if (!in_array((int)$options['dispatch_mode'], [2, 4, 5], true)) {
                throw new NotSupportedException('only support dispatch_mode=2,4,5');
            }
        } else {
            $options['dispatch_mode'] = 2;
        }

        unset($options['host'], $options['port']);
        $this->_settings = $options ?: [];

        $this->_swoole = new Server($this->_host, $this->_port);
        $this->_swoole->set($this->_settings);

        $this->_swoole->on('Start', [$this, 'onStart']);
        $this->_swoole->on('ManagerStart', [$this, 'onManagerStart']);
        $this->_swoole->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->_swoole->on('WorkerStop', [$this, 'onWorkerStop']);
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

        $this->request->prepare($_get, [], $_server, null, $request->cookie ?? []);
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

        try {
            $this->fireEvent('wsServer:start', $worker_id);
        } catch (Throwable $throwable) {
            $this->logger->error($throwable);
        }
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param int                      $worker_id
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function onWorkerStop($server, $worker_id)
    {
        try {
            $this->fireEvent('wsServer:stop', $worker_id);
        } catch (Throwable $throwable) {
            $this->logger->error($throwable);
        }
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param \Swoole\Http\Request     $request
     */
    public function onOpen(/** @noinspection PhpUnusedParameterInspection */ $server, $request)
    {
        $fd = $request->fd;

        try {
            $this->_prepareGlobals($request);
            $globals = $this->request->getContext();
            $globals->_REQUEST['fd'] = $fd;

            $this->_handler->onOpen($fd);
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

        foreach ($this->_messageCoroutines[$fd] ?? [] as $cid) {
            Coroutine::resume($cid);
        }
        unset($this->_messageCoroutines[$fd]);

        if ($cid = $this->_closeCoroutine[$fd] ?? false) {
            unset($this->_closeCoroutine[$fd]);
            Coroutine::resume($cid);
        }
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param int                      $fd
     */
    public function onClose($server, $fd)
    {
        if (!$server->isEstablished($fd)) {
            return;
        }

        if (!$old_context = $this->_contexts[$fd] ?? false) {
            $this->_closeCoroutine[$fd] = Coroutine::getCid();
            Coroutine::suspend();
            $old_context = $this->_contexts[$fd];
        }
        unset($this->_contexts[$fd]);

        /** @var \ArrayObject $current_context */
        $current_context = Coroutine::getContext();
        foreach ($old_context as $k => $v) {
            $current_context[$k] = $v;
        }

        try {
            $this->_handler->onClose($fd);
        } finally {
            null;
        }
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param Frame                    $frame
     */
    public function onMessage(/** @noinspection PhpUnusedParameterInspection */ $server, $frame)
    {
        $fd = $frame->fd;

        if (!$old_context = $this->_contexts[$fd] ?? false) {
            $this->_messageCoroutines[$fd][] = Coroutine::getCid();
            Coroutine::suspend();
            $old_context = $this->_contexts[$fd];
        }

        /** @var \ArrayObject $current_context */
        $current_context = Coroutine::getContext();
        foreach ($old_context as $k => $v) {
            $current_context[$k] = $v;
        }

        try {
            $this->_handler->onMessage($frame->fd, $frame->data);
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

        $this->_handler = $handler;

        echo PHP_EOL, str_repeat('+', 80), PHP_EOL;

        $settings = json_stringify($this->_settings);
        $this->log('info', sprintf('Listen on: %s:%d with setting: %s', $this->_host, $this->_port, $settings));
        $this->_swoole->start();

        echo sprintf('[%s][info]: shutdown', date('c')), PHP_EOL;
    }

    /**
     * @param int   $fd
     * @param mixed $data
     *
     * @return bool
     */
    public function push($fd, $data)
    {
        return @$this->_swoole->push($fd, is_string($data) ? $data : json_stringify($data));
    }

    public function broadcast($data)
    {
        $swoole = $this->_swoole;

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
        return $this->_swoole->disconnect($fd, 1000, '');
    }

    /**
     * @param int $fd
     *
     * @return bool
     */
    public function exists($fd)
    {
        return $this->_swoole->exist($fd);
    }

    public function reload()
    {
        $this->_swoole->reload();
    }
}