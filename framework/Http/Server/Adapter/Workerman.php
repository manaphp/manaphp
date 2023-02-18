<?php
/** @noinspection PhpUndefinedNamespaceInspection */
/** @noinspection PhpUndefinedClassInspection */
declare(strict_types=1);

namespace ManaPHP\Http\Server\Adapter;

use ManaPHP\Http\AbstractServer;
use Throwable;
use Workerman\Connection\ConnectionInterface;
use Workerman\Protocols\Http;
use Workerman\Worker;

/**
 * @property-read \ManaPHP\Http\RouterInterface                 $router
 * @property-read \ManaPHP\Http\Server\Adapter\WorkermanContext $context
 */
class Workerman extends AbstractServer
{
    protected array $settings = [];
    protected Worker $worker;
    protected array $_SERVER = [];
    protected int $max_request;
    protected int $request_count;

    public function __construct(array $settings = [], string $host = '0.0.0.0', int $port = 9501)
    {
        parent::__construct($host, $port);

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

        if (DIRECTORY_SEPARATOR === '/' && isset($settings['max_request']) && $settings['max_request'] > 0) {
            $this->max_request = $settings['max_request'];
        }

        $this->settings = $settings;
    }

    protected function prepareGlobals(): void
    {
        $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);

        $_SERVER += $this->_SERVER;

        $raw_body = $GLOBALS['HTTP_RAW_POST_DATA'] ?? null;
        $this->globals->prepare($_GET, $_POST, $_SERVER, $raw_body, $_COOKIE, $_FILES);

        unset($_GET, $_POST, $_REQUEST, $_FILES, $_COOKIE);
        foreach ($_SERVER as $k => $v) {
            if (!str_contains('DOCUMENT_ROOT,SERVER_SOFTWARE,SCRIPT_NAME,SCRIPT_FILENAME', $k)) {
                unset($_SERVER[$k]);
            }
        }
    }

    public function start(): void
    {
        echo PHP_EOL, str_repeat('+', 80), PHP_EOL;

        /** @noinspection HttpUrlsUsage */
        $this->worker = $worker = new Worker("http://{$this->host}:{$this->port}");

        $settings = json_stringify($this->settings);
        console_log('info', ['listen on: %s:%d with setting: %s', $this->host, $this->port, $settings]);
        echo 'ab';
        $worker->onMessage = [$this, 'onRequest'];

        if (isset($this->settings['worker_num'])) {
            $worker->count = (int)$this->settings['worker_num'];
        }

        global $argv;

        $argv[1] ??= 'start';

        if (DIRECTORY_SEPARATOR === '\\') {
            shell_exec("explorer.exe http://127.0.0.1:$this->port/" . $this->router->getPrefix());
        }

        $this->fireEvent('httpServer:start');

        Worker::runAll();

        console_log('info', 'shutdown');
    }

    public function onRequest(ConnectionInterface $connection): void
    {
        $this->prepareGlobals();

        try {
            $context = $this->context;
            $context->connection = $connection;
            $this->httpHandler->handle();
        } catch (Throwable $throwable) {
            $str = date('c') . ' ' . $throwable::class . ': ' . $throwable->getMessage() . PHP_EOL;
            $str .= '    at ' . $throwable->getFile() . ':' . $throwable->getLine() . PHP_EOL;
            $str .= preg_replace('/#\d+\s/', '    at ', $throwable->getTraceAsString());
            echo $str . PHP_EOL;
        }

        $this->contextor->resetContexts();

        if ($this->max_request && ++$this->request_count >= $this->max_request) {
            Worker::stopAll();
        }
    }

    public function send(): void
    {
        if (!is_string($this->response->getContent()) && !$this->response->hasFile()) {
            $this->fireEvent('response:stringify');

            if (!is_string($content = $this->response->getContent())) {
                $this->response->setContent(json_stringify($content));
            }
        }

        $this->fireEvent('request:responding');

        Http::header('HTTP', true, $this->response->getStatusCode());

        foreach ($this->response->getHeaders() as $name => $value) {
            Http::header("$name: $value");
        }

        Http::header('X-Request-Id: ' . $this->request->getRequestId());
        Http::header('X-Response-Time: ' . $this->request->getElapsedTime());

        $prefix = $this->router->getPrefix();
        foreach ($this->response->getCookies() as $cookie) {
            Http::setcookie(
                $cookie['name'],
                $cookie['value'],
                $cookie['expire'],
                $cookie['path'] === '' ? '' : ($prefix . $cookie['path']),
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

        $this->fireEvent('request:responded');
    }
}
