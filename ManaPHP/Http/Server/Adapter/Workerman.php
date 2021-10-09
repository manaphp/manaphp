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
 * @property-read \ManaPHP\Http\Server\Adapter\WorkermanContext $context
 */
class Workerman extends Server
{
    /**
     * @var array
     */
    protected $settings = [];

    /**
     * @var \Workerman\Worker
     */
    protected $worker;

    /**
     * @var array
     */
    protected $_SERVER = [];

    /**
     * @var int
     */
    protected $max_request;

    /**
     * @var int
     */
    protected $request_count;

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
            'SERVER_ADDR'     => $this->host,
            'PHP_SELF'        => '/' . basename($script_filename),
            'QUERY_STRING'    => '',
            'REQUEST_SCHEME'  => 'http',
        ];

        unset($_GET, $_POST, $_REQUEST, $_FILES, $_COOKIE);

        if (DIRECTORY_SEPARATOR === '/' && isset($options['max_request']) && $options['max_request'] > 0) {
            $this->max_request = $options['max_request'];
        }

        $this->settings = $options;
    }

    /**
     * @return void
     */
    protected function prepareGlobals()
    {
        $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);

        /** @noinspection AdditionOperationOnArraysInspection */
        $_SERVER += $this->_SERVER;

        $raw_body = $GLOBALS['HTTP_RAW_POST_DATA'] ?? null;
        $this->request->prepare($_GET, $_POST, $_SERVER, $raw_body, $_COOKIE, $_FILES);

        if (!$this->use_globals) {
            unset($_GET, $_POST, $_REQUEST, $_FILES, $_COOKIE);
            foreach ($_SERVER as $k => $v) {
                if (!str_contains('DOCUMENT_ROOT,SERVER_SOFTWARE,SCRIPT_NAME,SCRIPT_FILENAME', $k)) {
                    unset($_SERVER[$k]);
                }
            }
        }
    }

    /**
     * @return static
     */
    public function start()
    {
        echo PHP_EOL, str_repeat('+', 80), PHP_EOL;

        $this->worker = $worker = new Worker("http://{$this->host}:{$this->port}");

        $settings = json_stringify($this->settings);
        console_log('info', ['listen on: %s:%d with setting: %s', $this->host, $this->port, $settings]);
        echo 'ab';
        $worker->onMessage = [$this, 'onRequest'];

        if (isset($this->settings['worker_num'])) {
            $worker->count = (int)$this->settings['worker_num'];
        }

        global $argv;
        if (!isset($argv[1])) {
            $argv[1] = 'start';
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            shell_exec("explorer.exe http://127.0.0.1:$this->port/" . $this->router->getPrefix());
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
        $this->prepareGlobals();

        try {
            $context = $this->context;
            $context->connection = $connection;
            $this->httpHandler->handle();
        } catch (Throwable $throwable) {
            $str = date('c') . ' ' . get_class($throwable) . ': ' . $throwable->getMessage() . PHP_EOL;
            $str .= '    at ' . $throwable->getFile() . ':' . $throwable->getLine() . PHP_EOL;
            $str .= preg_replace('/#\d+\s/', '    at ', $throwable->getTraceAsString());
            echo $str . PHP_EOL;
        }

        global $__root_context;
        foreach ($__root_context as $owner) {
            unset($owner->context);
        }
        $__root_context = null;

        if ($this->max_request && ++$this->request_count >= $this->max_request) {
            Worker::stopAll();
        }
    }

    /**
     * @return void
     */
    public function send()
    {
        if (!is_string($this->response->getContent()) && !$this->response->hasFile()) {
            $this->fireEvent('response:stringify');

            if (!is_string($content = $this->response->getContent())) {
                $this->response->setContent(json_stringify($content));
            }
        }

        $this->fireEvent('response:sending');

        Http::header('HTTP', true, $this->response->getStatusCode());

        foreach ($this->response->getHeaders() as $name => $value) {
            Http::header("$name: $value");
        }

        Http::header('X-Request-Id: ' . $this->request->getRequestId());
        Http::header('X-Response-Time: ' . $this->request->getElapsedTime());

        foreach ($this->response->getCookies() as $cookie) {
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

        $content = $this->response->getContent();
        if ($this->response->getStatusCode() === 304) {
            $this->context->connection->close('');
        } elseif ($this->request->isHead()) {
            Http::header('Content-Length: ' . strlen($content));
            $this->context->connection->close('');
        } else {
            $this->context->connection->close($content);
        }

        $this->fireEvent('response:sent');
    }
}
