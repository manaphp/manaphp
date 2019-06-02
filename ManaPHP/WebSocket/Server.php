<?php
namespace ManaPHP\WebSocket;

use ManaPHP\Component;
use ManaPHP\ContextManager;
use Swoole\Coroutine;
use Swoole\Runtime;
use Swoole\WebSocket\Frame;

/**
 * Class Server
 * @package ManaPHP\WebSocket
 * @property-read \ManaPHP\Http\RequestInterface          $request
 * @property-read \ManaPHP\WebSocket\ApplicationInterface $app
 */
class Server extends Component implements ServerInterface
{
    /**
     * @var string
     */
    protected $_host = '0.0.0.0';

    /**
     * @var int
     */
    protected $_port = '8300';

    /**
     * @var array
     */
    protected $_server = [];

    /**
     * @var \Swoole\WebSocket\Server
     */
    protected $_swoole;

    /**
     * @var array
     */
    protected $_fd2cid = [];

    /**
     * Server constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['host'])) {
            $this->_host = $options['host'];
        }

        if (isset($options['port'])) {
            $this->_port = (int)$options['port'];
        }

        $script_filename = get_included_files()[0];
        $parts = explode('-', phpversion());
        $_SERVER = [
            'DOCUMENT_ROOT' => dirname($script_filename),
            'SCRIPT_FILENAME' => $script_filename,
            'SCRIPT_NAME' => '/' . basename($script_filename),
            'SERVER_ADDR' => $this->_host,
            'SERVER_SOFTWARE' => 'Swoole/' . SWOOLE_VERSION . ' ' . php_uname('s') . '/' . $parts[1] . ' PHP/' . $parts[0]
        ];

        $this->_server = $_SERVER + [
                'PHP_SELF' => '/' . basename($script_filename),
                'QUERY_STRING' => '',
                'REQUEST_SCHEME' => 'http',
            ];

        unset($_GET, $_POST, $_REQUEST, $_FILES, $_COOKIE);
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
        /** @noinspection AdditionOperationOnArraysInspection */
        $_server += $this->_server;

        foreach ($request->header ?: [] as $k => $v) {
            if (in_array($k, ['content-type', 'content-length'], true)) {
                $_server[strtoupper(strtr($k, '-', '_'))] = $v;
            } else {
                $_server['HTTP_' . strtoupper(strtr($k, '-', '_'))] = $v;
            }
        }

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
     * @return mixed|void
     */
    public function start()
    {
        Runtime::enableCoroutine();
        define('MANAPHP_COROUTINE', true);

        $swoole = new \Swoole\WebSocket\Server($this->_host, $this->_port);

        $swoole->on('open', function ($server, $req) {
            try {
                $fd = $req->fd;
                $cid = Coroutine::getCid();

                $this->_prepareGlobals($req);
                $this->app->onOpen($fd);
            } finally {
                $this->_fd2cid[$fd] = $cid;
            }
        });

        $swoole->on('close', function ($server, $fd) {
            $cid = Coroutine::getCid();

            try {
                while (!isset($this->_fd2cid[$fd])) {
                    usleep(0.01);
                    $this->log('info', 'open is not ready');
                }

                $old_cid = $this->_fd2cid[$fd];

                ContextManager::clones($old_cid, $cid);
                $this->app->onClose($fd);
            } finally {
                unset($this->_fd2cid[$fd]);
                ContextManager::reset($cid);
                ContextManager::reset($old_cid);
            }
        });

        $swoole->on('message', function ($server, Frame $frame) {
            $fd = $frame->fd;
            $cid = Coroutine::getCid();

            try {
                while (!isset($this->_fd2cid[$fd])) {
                    usleep(0.01);
                    $this->log('info', 'open is not ready');
                }

                $old_cid = $this->_fd2cid[$fd];

                ContextManager::clones($old_cid, $cid);
                $this->app->onMessage($fd, $frame->data);
            } finally {
                ContextManager::reset($cid);
            }
        });

        $this->_swoole = $swoole;

        $this->log('debug', 'listen on: ' . $this->_host . ':' . $this->_port);
        $swoole->start();
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
            $swoole->push($connection, $data);
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
}