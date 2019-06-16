<?php
namespace ManaPHP\WebSocket\Server\Adapter;

use ManaPHP\Component;
use ManaPHP\ContextManager;
use ManaPHP\WebSocket\ServerInterface;
use Swoole\Coroutine;
use Swoole\Process;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

/**
 * Class Server
 * @package ManaPHP\WebSocket
 * @property-read \ManaPHP\Http\RequestInterface $request
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
     * @var \ManaPHP\WebSocket\Server\HandlerInterface
     */
    protected $_handler;

    /**
     * @var array
     */
    protected $_fd2cid = [];

    /**
     * Server constructor.
     *
     * @param array $options
     */
    public function __construct($options = null)
    {
        $script_filename = get_included_files()[0];
        $parts = explode('-', phpversion());
        $_SERVER = [
            'DOCUMENT_ROOT' => dirname($script_filename),
            'SCRIPT_FILENAME' => $script_filename,
            'SCRIPT_NAME' => '/' . basename($script_filename),
            'SERVER_ADDR' => $this->_host,
            'SERVER_SOFTWARE' => 'Swoole/' . SWOOLE_VERSION . ' ' . php_uname('s') . '/' . $parts[1] . ' PHP/' . $parts[0],
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

        $this->_settings = $options;

        $this->_swoole = new Server($this->_host, $this->_port);
        $this->_swoole->set($this->_settings);

        $this->_swoole->on('open', [$this, '_onOpen']);
        $this->_swoole->on('close', [$this, '_onClose']);
        $this->_swoole->on('message', [$this, '_onMessage']);
    }

    public function log($level, $message)
    {
        echo sprintf('[%s][%s]: ', date('c'), $level), $message, PHP_EOL;
    }

    /**
     * @param \swoole_http_request $request
     */
    public function _prepareGlobals($request)
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
     * @param \Swoole\WebSocket\Server $server
     * @param \swoole_http_request     $req
     */
    public function _onOpen($server, $req)
    {
        try {
            $fd = $req->fd;
            $cid = Coroutine::getCid();

            $this->_prepareGlobals($req);
            $this->_handler->onOpen($fd);
        } finally {
            $this->_fd2cid[$fd] = $cid;
        }
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param int                      $fd
     */
    public function _onClose($server, $fd)
    {
        /** @var  \Swoole\WebSocket\Server $server */
        if (!$server->isEstablished($fd)) {
            return;
        }

        $cid = Coroutine::getCid();

        while (!isset($this->_fd2cid[$fd])) {
            Coroutine::sleep(0.01);
            $this->log('info', 'open is not ready');
        }

        try {
            $old_cid = $this->_fd2cid[$fd];

            ContextManager::clones($old_cid, $cid);
            $this->_handler->onClose($fd);
        } finally {
            unset($this->_fd2cid[$fd]);
            ContextManager::reset($cid);
            ContextManager::reset($old_cid);
        }
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param Frame                    $frame
     */
    public function _onMessage($server, $frame)
    {
        $fd = $frame->fd;
        $cid = Coroutine::getCid();

        try {
            while (!isset($this->_fd2cid[$fd])) {
                Coroutine::sleep(0.01);
                $this->log('info', 'open is not ready');
            }

            $old_cid = $this->_fd2cid[$fd];

            ContextManager::clones($old_cid, $cid);
            $this->_handler->onMessage($fd, $frame->data);
        } finally {
            ContextManager::reset($cid);
        }
    }

    /**
     * @param \ManaPHP\WebSocket\Server\HandlerInterface $handler
     *
     * @return void
     */
    public function start($handler)
    {
        foreach ($handler->getProcesses() as $process => $config) {
            $process = $this->_di->setShared($process, $config)->getShared($process);
            $this->addProcess($process);
        }

        $this->_handler = $handler;

        echo PHP_EOL, str_repeat('+', 80), PHP_EOL;

        $this->log('info',
            sprintf('starting listen on: %s:%d with setting: %s', $this->_host, $this->_port, json_encode($this->_settings, JSON_UNESCAPED_SLASHES)));
        $this->_swoole->start();

        echo sprintf('[%s][info]: shutdown', date('c')), PHP_EOL;
    }

    /**
     * @param int    $fd
     * @param string $data
     *
     * @return bool
     */
    public function push($fd, $data)
    {
        return $this->_swoole->push($fd, $data);
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

    /**
     * @param \ManaPHP\ProcessInterface $process
     *
     * @return void
     */
    public function addProcess($process)
    {
        $that = $this;
        $p = new Process(static function ($p) use ($process, $that) {
            unset($_SERVER['DOCUMENT_ROOT']);
            $that->logger->debug('aaaafdfd');
            $process->run();
        });

        $this->_swoole->addProcess($p);
    }
}