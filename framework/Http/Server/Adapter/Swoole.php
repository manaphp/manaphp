<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Adapter;

use ManaPHP\AliasInterface;
use ManaPHP\Context\ContextTrait;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Di\ConfigInterface;
use ManaPHP\Di\Lazy;
use ManaPHP\Helper\Ip;
use ManaPHP\Http\AbstractServer;
use ManaPHP\Http\Response\AppenderInterface;
use ManaPHP\Http\RouterInterface;
use ManaPHP\Http\Server\Event\RequestResponded;
use ManaPHP\Http\Server\Event\RequestResponsing;
use ManaPHP\Http\Server\Event\ResponseStringify;
use ManaPHP\Http\Server\Event\ServerBeforeShutdown;
use ManaPHP\Http\Server\Event\ServerClose;
use ManaPHP\Http\Server\Event\ServerConnect;
use ManaPHP\Http\Server\Event\ServerFinish;
use ManaPHP\Http\Server\Event\ServerManagerStart;
use ManaPHP\Http\Server\Event\ServerManagerStop;
use ManaPHP\Http\Server\Event\ServerPacket;
use ManaPHP\Http\Server\Event\ServerPipeMessage;
use ManaPHP\Http\Server\Event\ServerReady;
use ManaPHP\Http\Server\Event\ServerShutdown;
use ManaPHP\Http\Server\Event\ServerStart;
use ManaPHP\Http\Server\Event\ServerTask;
use ManaPHP\Http\Server\Event\ServerWorkerError;
use ManaPHP\Http\Server\Event\ServerWorkerExit;
use ManaPHP\Http\Server\Event\ServerWorkerStart;
use ManaPHP\Http\Server\Event\ServerWorkerStop;
use ManaPHP\Http\Server\StaticHandlerInterface;
use Psr\Log\LoggerInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Swoole\Runtime;
use Throwable;

class Swoole extends AbstractServer
{
    use ContextTrait;

    #[Autowired] protected AliasInterface $alias;
    #[Autowired] protected StaticHandlerInterface|Lazy $staticHandler;
    #[Autowired] protected ConfigInterface $config;
    #[Autowired] protected LoggerInterface $logger;

    #[Autowired] protected array $settings = [];

    #[Config] protected string $app_id;

    protected Server $swoole;
    protected array $_SERVER;

    public function __construct()
    {
        $script_filename = get_included_files()[0];
        $document_root = \dirname($script_filename);
        $_SERVER['DOCUMENT_ROOT'] = $document_root;

        $this->_SERVER = [
            'DOCUMENT_ROOT'   => $document_root,
            'SCRIPT_FILENAME' => $script_filename,
            'SCRIPT_NAME'     => '/' . basename($script_filename),
            'SERVER_ADDR'     => $this->host === '0.0.0.0' ? Ip::local() : $this->host,
            'SERVER_PORT'     => $this->port,
            'SERVER_SOFTWARE' => 'Swoole/' . SWOOLE_VERSION . ' (' . PHP_OS . ') PHP/' . PHP_VERSION,
            'PHP_SELF'        => '/' . basename($script_filename),
            'QUERY_STRING'    => '',
            'REQUEST_SCHEME'  => 'http',
        ];

        $this->settings['enable_coroutine'] = MANAPHP_COROUTINE_ENABLED;

        if (isset($this->settings['max_request']) && $this->settings['max_request'] < 1) {
            $this->settings['max_request'] = 1;
        }

        if (!empty($this->settings['enable_static_handler'])) {
            $this->settings['document_root'] = $document_root;
        }

        $this->swoole = new Server($this->host, $this->port);
        $this->swoole->set($this->settings);
        $this->swoole->on('Start', [$this, 'onStart']);
        $this->swoole->on('BeforeShutdown', [$this, 'onBeforeShutdown']);
        $this->swoole->on('Shutdown', [$this, 'onShutdown']);
        $this->swoole->on('ManagerStart', [$this, 'onManagerStart']);
        $this->swoole->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->swoole->on('WorkerStop', [$this, 'onWorkerStop']);
        $this->swoole->on('WorkerExit', [$this, 'onWorkerExit']);
        $this->swoole->on('Connect', [$this, 'onConnect']);
        $this->swoole->on('Packet', [$this, 'onPacket']);
        $this->swoole->on('Close', [$this, 'onClose']);
        $this->swoole->on('Task', [$this, 'onTask']);
        $this->swoole->on('Finish', [$this, 'onFinish']);
        $this->swoole->on('PipeMessage', [$this, 'onPipeMessage']);
        $this->swoole->on('WorkerError', [$this, 'onWorkerError']);
        $this->swoole->on('ManagerStop', [$this, 'onManagerStop']);
        $this->swoole->on('Request', [$this, 'onRequest']);
    }

    protected function prepareGlobals(Request $request): void
    {
        $_server = array_change_key_case($request->server, CASE_UPPER);
        unset($_server['SERVER_SOFTWARE']);

        foreach ($request->header ?: [] as $k => $v) {
            if (\in_array($k, ['content-type', 'content-length'], true)) {
                $_server[strtoupper(strtr($k, '-', '_'))] = $v;
            } else {
                $_server['HTTP_' . strtoupper(strtr($k, '-', '_'))] = $v;
            }
        }

        $_server += $this->_SERVER;

        $_get = $request->get ?: [];
        $_post = $request->post ?: [];
        $raw_body = $request->rawContent();
        $this->globals->prepare($_get, $_post, $_server, $raw_body, $request->cookie ?? [], $request->files ?? []);
    }

    protected function dispatchEvent(object $object): void
    {
        try {
            $this->eventDispatcher->dispatch($object);
        } catch (Throwable $throwable) {
            $this->logger->error($throwable->getMessage(), ['exception' => $throwable]);
        }
    }

    public function onStart(Server $server): void
    {
        @cli_set_process_title(sprintf('%s.swoole-master', $this->app_id));

        $this->dispatchEvent(new ServerStart($server));
    }

    public function onBeforeShutdown(Server $server): void
    {
        $this->dispatchEvent(new ServerBeforeShutdown($server));
    }

    public function onShutdown(Server $server): void
    {
        $this->dispatchEvent(new ServerShutdown($server));
    }

    public function onManagerStart(Server $server): void
    {
        @cli_set_process_title(sprintf('%s.swoole-manager', $this->app_id));

        $this->dispatchEvent(new ServerManagerStart($server));
    }

    public function onWorkerStart(Server $server, int $worker_id): void
    {
        $worker_num = $server->setting['worker_num'];
        if ($worker_id < $worker_num) {
            @cli_set_process_title(sprintf('%s.swoole-worker.%d', $this->app_id, $worker_id));
        } else {
            $tasker_id = $worker_id - $worker_num;
            @cli_set_process_title(sprintf('%s.swoole-worker.%d.%d', $this->app_id, $worker_id, $tasker_id));
        }

        $this->dispatchEvent(new ServerWorkerStart($server, $worker_id));
    }

    public function onWorkerStop(Server $server, int $worker_id): void
    {
        $this->dispatchEvent(new ServerWorkerStop($server, $worker_id));
    }

    public function onWorkerExit(Server $server, int $worker_id): void
    {
        $this->dispatchEvent(new ServerWorkerExit($server, $worker_id));
    }

    public function onConnect(Server $server, int $fd, int $reactor_id): void
    {
        $this->dispatchEvent(new ServerConnect($server, $fd, $reactor_id));
    }

    public function onPacket(Server $server, string $data, array $client): void
    {
        $this->dispatchEvent(new ServerPacket($server, $data, $client));
    }

    public function onClose(Server $server, int $fd, int $reactor_id): void
    {
        $this->dispatchEvent(new ServerClose($server, $fd, $reactor_id));
    }

    public function onTask(Server $server, int $worker_id, int $src_worker_id, mixed $data): void
    {
        $this->dispatchEvent(new ServerTask($server, $worker_id, $src_worker_id, $data));
    }

    public function onFinish(Server $server, int $worker_id, mixed $data): void
    {
        $this->dispatchEvent(new ServerFinish($server, $worker_id, $data));
    }

    public function onPipeMessage(Server $server, int $src_worker_id, mixed $message): void
    {
        $this->dispatchEvent(new ServerPipeMessage($server, $src_worker_id, $message));
    }

    public function onWorkerError(Server $server, int $worker_id, int $worker_pid, int $exit_code, int $signal): void
    {
        $this->dispatchEvent(new ServerWorkerError($server, $worker_id, $worker_pid, $exit_code, $signal));
    }

    public function onManagerStop(Server $server): void
    {
        $this->dispatchEvent(new ServerManagerStop($server));
    }

    public function start(): void
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            Runtime::enableCoroutine();
        }

        $this->bootstrap();

        echo PHP_EOL, str_repeat('+', 80), PHP_EOL;

        $settings = json_stringify($this->settings);
        console_log('info', ['listen on: %s:%d with setting: %s', $this->host, $this->port, $settings]);
        $this->dispatchEvent(new ServerReady());
        $prefix = $this->config->get(RouterInterface::class)['prefix'] ?? '';
        $prefix = ltrim($prefix, '?');
        /** @noinspection HttpUrlsUsage */
        console_log('info', sprintf('http://%s:%s%s', $this->host, $this->port, $prefix));
        $this->swoole->start();
        console_log('info', 'shutdown');
    }

    public function onRequest(Request $request, Response $response): void
    {
        $uri = $request->server['request_uri'];
        if ($uri === '/favicon.ico') {
            $response->status(404);
            $response->end();
            return;
        }

        $this->prepareGlobals($request);

        if (!empty($this->settings['enable_static_handler']) && $this->staticHandler->isFile($uri)) {
            if (($file = $this->staticHandler->getFile($uri)) !== null) {
                $response->header('Content-Type', $this->staticHandler->getMimeType($file));
                $response->sendfile($file);
            } else {
                $response->status(404, 'Not Found');
                $response->end('');
            }
        } else {
            /** @var SwooleContext $context */
            $context = $this->getContext();

            $context->response = $response;

            try {
                $this->httpHandler->handle();
            } catch (Throwable $throwable) {
                $str = date('c') . ' ' . $throwable::class . ': ' . $throwable->getMessage() . PHP_EOL;
                $str .= '    at ' . $throwable->getFile() . ':' . $throwable->getLine() . PHP_EOL;
                $str .= preg_replace('/#\d+\s/', '    at ', $throwable->getTraceAsString());
                echo $str . PHP_EOL;
            }
        }

        $this->contextor->resetContexts();
    }

    public function send(): void
    {
        if (!\is_string($this->response->getContent()) && !$this->response->hasFile()) {
            $this->dispatchEvent(new ResponseStringify($this->response));
            if (!\is_string($content = $this->response->getContent())) {
                $this->response->setContent(json_stringify($content));
            }
        }

        $this->dispatchEvent(new RequestResponsing($this->request, $this->response));

        foreach ($this->response->getAppenders() as $appender) {
            /** @var string|AppenderInterface $appender */
            $appender = $this->container->get($appender);
            $appender->append($this->request, $this->response);
        }

        /** @var SwooleContext $context */
        $context = $this->getContext();

        $response = $context->response;

        $http_code = $this->response->getStatusCode();
        $reason = $this->response->getStatusText($http_code);
        $response->status($http_code, $reason);

        foreach ($this->response->getHeaders() as $name => $value) {
            $response->header($name, $value, false);
        }

        $prefix = $this->router->getPrefix();
        foreach ($this->response->getCookies() as $cookie) {
            $response->cookie(
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
            $response->end('');
        } elseif ($this->request->isHead()) {
            $response->header('Content-Length', (string)\strlen($content), false);
            $response->end('');
        } elseif ($file = $this->response->getFile()) {
            $response->sendfile($this->alias->resolve($file));
        } else {
            $response->end($content);
        }

        $this->dispatchEvent(new RequestResponded($this->request, $this->response));
    }
}
