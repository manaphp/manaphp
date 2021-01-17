<?php
/** @noinspection PhpUndefinedFieldInspection */
/** @noinspection PhpUndefinedMethodInspection */
/** @noinspection PhpUndefinedNamespaceInspection */
/** @noinspection PhpUndefinedClassInspection */

namespace ManaPHP\Http\Server\Adapter;

use ManaPHP\Http\Server;
use Throwable;
use Workerman\Protocols\Http;
use Workerman\Worker;

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

class WorkermanContext
{
    /**
     * @var \Workerman\Connection\ConnectionInterface
     */
    public $connection;
}

/**
 * @property-read \ManaPHP\Http\RouterInterface                 $router
 * @property-read \ManaPHP\Http\Server\Adapter\WorkermanContext $_context
 */
class Workerman extends Server
{
    /**
     * @var array
     */
    protected $_settings = [];

    /**
     * @var \Workerman\Worker
     */
    protected $_worker;

    /**
     * @var \ManaPHP\Http\Server\HandlerInterface
     */
    protected $_handler;

    /**
     * @var array
     */
    protected $_SERVER = [];

    /**
     * @var int
     */
    protected $_max_request;

    /**
     * @var int
     */
    protected $_request_count;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        $script_filename = get_included_files()[0];
        $this->_SERVER = [
            'DOCUMENT_ROOT'   => dirname($script_filename),
            'SCRIPT_FILENAME' => $script_filename,
            'SCRIPT_NAME'     => '/' . basename($script_filename),
            'SERVER_ADDR'     => $this->_host,
            'PHP_SELF'        => '/' . basename($script_filename),
            'QUERY_STRING'    => '',
            'REQUEST_SCHEME'  => 'http',
        ];

        unset($_GET, $_POST, $_REQUEST, $_FILES, $_COOKIE);

        if (DIRECTORY_SEPARATOR === '/' && isset($options['max_request']) && $options['max_request'] > 0) {
            $this->_max_request = $options['max_request'];
        }

        $this->_settings = $options;
    }

    /**
     * @return void
     */
    protected function _prepareGlobals()
    {
        $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);

        /** @noinspection AdditionOperationOnArraysInspection */
        $_SERVER += $this->_SERVER;

        $raw_body = $GLOBALS['HTTP_RAW_POST_DATA'] ?? null;
        $this->request->prepare($_GET, $_POST, $_SERVER, $raw_body, $_COOKIE, $_FILES);

        if (!$this->_use_globals) {
            unset($_GET, $_POST, $_REQUEST, $_FILES, $_COOKIE);
            foreach ($_SERVER as $k => $v) {
                if (!str_contains('DOCUMENT_ROOT,SERVER_SOFTWARE,SCRIPT_NAME,SCRIPT_FILENAME', $k)) {
                    unset($_SERVER[$k]);
                }
            }
        }
    }

    /**
     * @param \ManaPHP\Http\Server\HandlerInterface $handler
     *
     * @return static
     */
    public function start($handler)
    {
        echo PHP_EOL, str_repeat('+', 80), PHP_EOL;

        $this->_worker = $worker = new Worker("http://{$this->_host}:{$this->_port}");

        $this->_handler = $handler;

        $settings = json_stringify($this->_settings);
        console_log('info', ['listen on: %s:%d with setting: %s', $this->_host, $this->_port, $settings]);
        echo 'ab';
        $worker->onMessage = [$this, 'onRequest'];

        if (isset($this->_settings['worker_num'])) {
            $worker->count = (int)$this->_settings['worker_num'];
        }

        global $argv;
        if (!isset($argv[1])) {
            $argv[1] = 'start';
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            shell_exec("explorer.exe http://127.0.0.1:$this->_port/" . $this->router->getPrefix());
        }

        Worker::runAll();

        console_log('info', 'shutdown');

        return $this;
    }

    /**
     * @param \Workerman\Connection\ConnectionInterface $connection
     *
     * @return void
     */
    public function onRequest($connection)
    {
        $this->_prepareGlobals();

        try {
            $context = $this->_context;
            $context->connection = $connection;
            $this->_handler->handle();
        } catch (Throwable $throwable) {
            $str = date('c') . ' ' . get_class($throwable) . ': ' . $throwable->getMessage() . PHP_EOL;
            $str .= '    at ' . $throwable->getFile() . ':' . $throwable->getLine() . PHP_EOL;
            $str .= preg_replace('/#\d+\s/', '    at ', $throwable->getTraceAsString());
            echo $str . PHP_EOL;
        }

        global $__root_context;
        foreach ($__root_context as $owner) {
            unset($owner->_context);
        }
        $__root_context = null;

        if ($this->_max_request && ++$this->_request_count >= $this->_max_request) {
            Worker::stopAll();
        }
    }

    /**
     * @param \ManaPHP\Http\ResponseContext $context
     *
     * @return void
     */
    public function send($context)
    {
        $this->fireEvent('response:sending', compact('context'));

        Http::header('HTTP', true, $context->status_code);

        foreach ($context->headers as $name => $value) {
            Http::header("$name: $value");
        }

        Http::header('X-Request-Id: ' . $this->request->getRequestId());
        Http::header('X-Response-Time: ' . $this->request->getElapsedTime());

        foreach ($context->cookies as $cookie) {
            Http::setcookie(
                $cookie['name'],
                $cookie['value'],
                $cookie['expire'],
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httponly']
            );
        }

        if ($context->status_code === 304) {
            $this->_context->connection->close('');
        } elseif ($this->request->isHead()) {
            Http::header('Content-Length: ' . strlen($context->content));
            $this->_context->connection->close('');
        } else {
            $this->_context->connection->close($context->content);
        }

        $this->fireEvent('response:sent', compact('context'));
    }
}
