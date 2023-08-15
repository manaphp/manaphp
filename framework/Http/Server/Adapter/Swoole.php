<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Adapter;

use ManaPHP\AliasInterface;
use ManaPHP\ConfigInterface;
use ManaPHP\Context\ContextTrait;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Helper\Ip;
use ManaPHP\Http\AbstractServer;
use ManaPHP\Http\Server\StaticHandlerInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Swoole\Runtime;
use Throwable;

class Swoole extends AbstractServer
{
    use ContextTrait;

    #[Inject] protected ConfigInterface $config;
    #[Inject] protected AliasInterface $alias;
    #[Inject] protected StaticHandlerInterface $staticHandler;

    #[Value] protected array $settings = [];
    protected Server $swoole;
    protected array $_SERVER;

    public function __construct()
    {
        $script_filename = get_included_files()[0];
        $document_root = dirname($script_filename);
        $_SERVER['DOCUMENT_ROOT'] = $document_root;

        parent::__construct();

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
        $this->swoole->on('Start', [$this, 'onMasterStart']);
        $this->swoole->on('ManagerStart', [$this, 'onManagerStart']);
        $this->swoole->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->swoole->on('request', [$this, 'onRequest']);
    }

    protected function prepareGlobals(Request $request): void
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

        $_server += $this->_SERVER;

        $_get = $request->get ?: [];
        $_post = $request->post ?: [];
        $raw_body = $request->rawContent();
        $this->globals->prepare($_get, $_post, $_server, $raw_body, $request->cookie ?? [], $request->files ?? []);
    }

    public function onMasterStart(Server $server): void
    {
        @cli_set_process_title(sprintf('manaphp %s: master', $this->config->get('id')));

        $this->fireEvent('httpServer:masterStart', compact('server'));
    }

    public function onManagerStart(): void
    {
        @cli_set_process_title(sprintf('manaphp %s: manager', $this->config->get("id")));

        $this->fireEvent('httpServer:managerStart', ['server' => $this->swoole]);
    }

    public function onWorkerStart(Server $server, int $worker_id): void
    {
        @cli_set_process_title(sprintf('manaphp %s: worker/%d', $this->config->get("id"), $worker_id));

        $this->fireEvent('httpServer::workerStart', compact('server', 'worker_id'));
    }

    public function start(): void
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            Runtime::enableCoroutine();
        }

        echo PHP_EOL, str_repeat('+', 80), PHP_EOL;

        $settings = json_stringify($this->settings);
        console_log('info', ['listen on: %s:%d with setting: %s', $this->host, $this->port, $settings]);
        $this->fireEvent('httpServer:start', ['server' => $this->swoole]);
        /** @noinspection HttpUrlsUsage */
        console_log('info', sprintf('http://%s:%s%s', $this->host, $this->port, $this->router->getPrefix()));
        $this->swoole->start();
        console_log('info', 'shutdown');
    }

    public function onRequest(Request $request, Response $response): void
    {
        $uri = $request->server['request_uri'];
        if ($uri === '/favicon.ico') {
            $response->status(404);
            $response->end();
        } elseif (!empty($this->settings['enable_static_handler']) && $this->router->getPrefix() !== ''
            && $this->staticHandler->isFile($uri)
        ) {
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
                $this->prepareGlobals($request);

                $this->httpHandler->handle();
            } catch (Throwable $throwable) {
                $str = date('c') . ' ' . $throwable::class . ': ' . $throwable->getMessage() . PHP_EOL;
                $str .= '    at ' . $throwable->getFile() . ':' . $throwable->getLine() . PHP_EOL;
                $str .= preg_replace('/#\d+\s/', '    at ', $throwable->getTraceAsString());
                echo $str . PHP_EOL;
            }

            $this->contextor->resetContexts();
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

        /** @var SwooleContext $context */
        $context = $this->getContext();

        $response = $context->response;

        $response->status($this->response->getStatusCode());

        foreach ($this->response->getHeaders() as $name => $value) {
            $response->header($name, $value, false);
        }

        $response->header('X-Request-Id', $this->request->getRequestId(), false);
        $response->header('X-Response-Time', sprintf('%.3f', $this->request->getElapsedTime()), false);

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
            $response->header('Content-Length', strlen($content), false);
            $response->end('');
        } elseif ($file = $this->response->getFile()) {
            $response->sendfile($this->alias->resolve($file));
        } else {
            $response->end($content);
        }

        $this->fireEvent('request:responded');
    }

    public function dump(): array
    {
        $data = parent::dump();
        unset($data['swoole']);
        return $data;
    }
}
