<?php
namespace ManaPHP\Http\Server\Adapter;

use ManaPHP\Component;
use ManaPHP\ContextManager;
use ManaPHP\Http\ServerInterface;
use ReflectionClass;
use Throwable;
use Workerman\Protocols\Http;
use Workerman\Worker;

class WorkermanContext
{
    /**
     * @var \Workerman\Connection\ConnectionInterface
     */
    public $connection;
}

/**
 * Class Workerman
 * @package ManaPHP\Http\Server\Adapter
 *
 * @property-read \ManaPHP\Http\RequestInterface                $request
 * @property-read \ManaPHP\Http\Response                        $response
 * @property-read \ManaPHP\Http\Server\Adapter\WorkermanContext $_context
 */
class Workerman extends Component implements ServerInterface
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
     * @var bool
     */
    protected $_compatible_globals = false;

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
     * @var string
     */
    protected $_root_dir;

    /**
     * @var array
     */
    protected $_dirs;

    /**
     * @var array
     */
    protected $_mime_types;

    /**
     * @var int
     */
    protected $_max_request;

    /**
     * @var int
     */
    protected $_request_count;

    /**
     * Swoole constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $script_filename = get_included_files()[0];
        $this->_root_dir = str_replace('\\', '/', dirname($script_filename));
        $this->_SERVER = [
            'DOCUMENT_ROOT' => dirname($script_filename),
            'SCRIPT_FILENAME' => $script_filename,
            'SCRIPT_NAME' => '/' . basename($script_filename),
            'SERVER_ADDR' => $this->_host,
            'PHP_SELF' => '/' . basename($script_filename),
            'QUERY_STRING' => '',
            'REQUEST_SCHEME' => 'http',
        ];

        unset($_GET, $_POST, $_REQUEST, $_FILES, $_COOKIE);

        $this->alias->set('@web', '');
        $this->alias->set('@asset', '');

        if (isset($options['compatible_globals'])) {
            $this->_compatible_globals = (bool)$options['compatible_globals'];
            unset($options['compatible_globals']);
        }

        if (isset($options['host'])) {
            $this->_host = $options['host'];
        }

        if (isset($options['port'])) {
            $this->_port = (int)$options['port'];
        }

        if (isset($options['max_request']) && $options['max_request'] < 1) {
            $options['max_request'] = 1;
        }

        if (DIRECTORY_SEPARATOR === '/' && isset($options['max_request'])) {
            $this->_max_request = $options['max_request'];
        }

        $this->_settings = $options;

        if (!empty($options['enable_static_handler'])) {
            foreach (glob("$this->_root_dir/*", GLOB_ONLYDIR) as $dir) {
                $this->_dirs[] = basename($dir);
            }

            $rc = new ReflectionClass(Worker::class);
            foreach (file(dirname($rc->getFileName()) . '/Protocols/Http/mime.types', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (strpos($line, ';') === false) {
                    continue;
                }

                $line = trim($line);
                $line = trim($line, ';');

                $parts = preg_split('#\s+#', $line, -1, PREG_SPLIT_NO_EMPTY);
                if (count($parts) < 2) {
                    continue;
                }

                foreach ($parts as $k => $part) {
                    if ($k !== 0) {
                        $this->_mime_types[$part] = $parts[0];
                    }
                }
            }
        }
    }

    /**
     * @return void
     */
    public function _prepareGlobals()
    {
        $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);

        $_SERVER += $this->_SERVER;
        if (!isset($_GET['_url'])) {
            $uri = $_SERVER['REQUEST_URI'];
            $_GET['_url'] = $_REQUEST['_url'] = ($pos = strpos($uri, '?')) === false ? $uri : substr($uri, 0, $pos);
        }

        if (!$_POST && isset($_SERVER['REQUEST_METHOD']) && !in_array($_SERVER['REQUEST_METHOD'], ['GET', 'OPTIONS'], true)) {
            $data = $GLOBALS['HTTP_RAW_POST_DATA'];

            if (isset($_SERVER['CONTENT_TYPE'])
                && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
                $_POST = json_decode($data, true, 16);
            } else {
                parse_str($data, $_POST);
            }

            if (is_array($_POST)) {
                /** @noinspection AdditionOperationOnArraysInspection */
                $_REQUEST = $_POST + $_GET;
            } else {
                $_POST = [];
            }
        }

        $globals = $this->request->getGlobals();

        $globals->_GET = $_GET;
        $globals->_POST = $_POST;
        $globals->_REQUEST = $_REQUEST;
        $globals->_FILES = $_FILES;
        $globals->_COOKIE = $_COOKIE;
        $globals->_SERVER = $_SERVER;

        if (!$this->_compatible_globals) {
            unset($_GET, $_POST, $_REQUEST, $_FILES, $_COOKIE);
            foreach ($_SERVER as $k => $v) {
                if (strpos('DOCUMENT_ROOT,SERVER_SOFTWARE,SCRIPT_NAME,SCRIPT_FILENAME', $k) === false) {
                    unset($_SERVER[$k]);
                }
            }
        }
    }

    public function log($level, $message)
    {
        echo sprintf('[%s][%s]: ', date('c'), $level), $message, PHP_EOL;
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

        $this->log('info',
            sprintf('starting listen on: %s:%d with setting: %s', $this->_host, $this->_port, json_encode($this->_settings, JSON_UNESCAPED_SLASHES)));
        echo 'ab';
        $worker->onMessage = [$this, 'onRequest'];

        if (isset($this->_settings['worker_num'])) {
            $worker->count = (int)$this->_settings['worker_num'];
        }

        global $argv;
        if (!isset($argv[1])) {
            $argv[1] = 'start';
        }

        Worker::runAll();

        echo sprintf('[%s][info]: shutdown', date('c')), PHP_EOL;

        return $this;
    }

    /**
     * @return bool|string
     */
    protected function _getStaticFile()
    {
        $uri = $_SERVER['REQUEST_URI'];
        $file = ($pos = strpos($uri, '?')) === false ? $uri : substr($uri, 0, $pos);
        if (($pos = strpos($file, '/', 1)) === false) {
            return false;
        } else {
            $dir = substr($file, 1, $pos - 1);
            if (in_array($dir, $this->_dirs, true)) {
                if (!is_file($path = $this->_root_dir . $file)) {
                    return false;
                }

                if (DIRECTORY_SEPARATOR === '/') {
                    return realpath($path) === $path ? $path : false;
                } else {
                    return str_replace('\\', '/', realpath($path)) === $path ? $path : false;
                }
            } else {
                return false;
            }
        }
    }

    /**
     * @param \Workerman\Connection\ConnectionInterface $connection
     *
     * @return bool
     */
    protected function _onRequestStaticFile($connection)
    {
        try {
            if ($_SERVER['REQUEST_URI'] === '/favicon.ico') {
                if (is_file($file = $this->_root_dir . '/favicon.ico')) {
                    Http::header('Content-Type: image/x-icon');
                    $connection->close(file_get_contents($file));
                } else {
                    Http::header('Http-Code:', true, 404);
                    $connection->close('');
                }

                return true;
            }
            if ($this->_dirs && $file = $this->_getStaticFile()) {
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                $mime_type = isset($this->_mime_types[$ext]) ? $this->_mime_types[$ext] : 'application/octet-stream';
                Http::header('Content-Type: ' . $mime_type);
                $connection->close(file_get_contents($file));
                return true;
            }
        } catch (Throwable $exception) {
            $str = date('c') . ' ' . get_class($exception) . ': ' . $exception->getMessage() . PHP_EOL;
            $str .= '    at ' . $exception->getFile() . ':' . $exception->getLine() . PHP_EOL;
            $traces = $exception->getTraceAsString();
            $str .= preg_replace('/#\d+\s/', '    at ', $traces);
            echo $str . PHP_EOL;
            return true;
        }

        return false;
    }

    /**
     * @param \Workerman\Connection\ConnectionInterface $connection
     */
    public function onRequest($connection)
    {
        if ($this->_onRequestStaticFile($connection)) {
            return;
        }

        try {
            $context = $this->_context;
            $context->connection = $connection;

            $this->_prepareGlobals();

            $this->_handler->handle();
        } catch (Throwable $exception) {
            $str = date('c') . ' ' . get_class($exception) . ': ' . $exception->getMessage() . PHP_EOL;
            $str .= '    at ' . $exception->getFile() . ':' . $exception->getLine() . PHP_EOL;
            $traces = $exception->getTraceAsString();
            $str .= preg_replace('/#\d+\s/', '    at ', $traces);
            echo $str . PHP_EOL;
        } finally {
            ContextManager::reset();
        }

        if ($this->_max_request && ++$this->_request_count >= $this->_max_request) {
            Worker::stopAll();
        }
    }

    /**
     * @param \ManaPHP\Http\ResponseInterface $response
     */
    public function send($response)
    {
        $this->eventsManager->fireEvent('response:beforeSend', $this, $response);

        $connection = $this->_context->connection;

        /** @var \ManaPHP\Http\ResponseContext $response */
        $response = $this->response->_context;

        Http::header('HTTP', true, $response->status_code);

        foreach ($response->headers as $name => $value) {
            Http::header("$name: $value");
        }

        $server = $this->request->getGlobals()->_SERVER;

        if (isset($server['HTTP_X_REQUEST_ID']) && !isset($response_context->headers['X-Request-Id'])) {
            Http::header('X-Request-Id: ' . $server['HTTP_X_REQUEST_ID']);
        }

        Http::header('X-Response-Time: ' . sprintf('%.3f', microtime(true) - $server['REQUEST_TIME_FLOAT']));

        foreach ($response->cookies as $cookie) {
            Http::setcookie($cookie['name'], $cookie['value'], $cookie['expire'],
                $cookie['path'], $cookie['domain'], $cookie['secure'],
                $cookie['httpOnly']);
        }

        $connection->close((string)$response->content);
        $this->eventsManager->fireEvent('response:afterSend', $this, $response);
    }

    public function __debugInfo()
    {
        $data = parent::__debugInfo();
        unset($data['_swoole']);

        return $data;
    }
}