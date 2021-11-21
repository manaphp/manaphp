<?php

namespace ManaPHP\Http\Server\Adapter;

use ManaPHP\Helper\Ip;
use ManaPHP\Http\AbstractServer;
use Swoole\Runtime;
use Throwable;

/**
 * @property-read \ManaPHP\ConfigInterface                   $config
 * @property-read \ManaPHP\AliasInterface                    $alias
 * @property-read \ManaPHP\Http\RouterInterface              $router
 * @property-read \ManaPHP\Http\Server\Adapter\SwooleContext $context
 */
class Swoole extends AbstractServer
{
    /**
     * @var array
     */
    protected $settings = [];

    /**
     * @var \Swoole\Http\Server
     */
    protected $swoole;

    /**
     * @var array
     */
    protected $_SERVER;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        $script_filename = get_included_files()[0];
        $this->_SERVER = [
            'DOCUMENT_ROOT'   => dirname($script_filename),
            'SCRIPT_FILENAME' => $script_filename,
            'SCRIPT_NAME'     => '/' . basename($script_filename),
            'SERVER_ADDR'     => $this->host === '0.0.0.0' ? Ip::local() : $this->host,
            'SERVER_PORT'     => $this->port,
            'SERVER_SOFTWARE' => 'Swoole/' . SWOOLE_VERSION . ' (' . PHP_OS . ') PHP/' . PHP_VERSION,
            'PHP_SELF'        => '/' . basename($script_filename),
            'QUERY_STRING'    => '',
            'REQUEST_SCHEME'  => 'http',
        ];

        $options['enable_coroutine'] = MANAPHP_COROUTINE_ENABLED;

        if (isset($options['max_request']) && $options['max_request'] < 1) {
            $options['max_request'] = 1;
        }

        if (!empty($options['enable_static_handler'])) {
            $options['document_root'] = $this->_SERVER['DOCUMENT_ROOT'];
        }

        parent::__construct($options);

        unset($options['use_globals'], $options['host'], $options['port']);

        $this->settings = $options;

        $this->swoole = new \Swoole\Http\Server($this->host, $this->port);
        $this->swoole->set($this->settings);
        $this->swoole->on('Start', [$this, 'onMasterStart']);
        $this->swoole->on('ManagerStart', [$this, 'onManagerStart']);
        $this->swoole->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->swoole->on('request', [$this, 'onRequest']);
    }

    /**
     * @param \Swoole\Http\Request $request
     *
     * @return void
     */
    protected function prepareGlobals($request)
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

        /** @noinspection AdditionOperationOnArraysInspection */
        $_server += $this->_SERVER;

        $_get = $request->get ?: [];
        $_post = $request->post ?: [];
        $raw_body = $request->rawContent();
        $this->globals->prepare($_get, $_post, $_server, $raw_body, $request->cookie ?? [], $request->files ?? []);
    }

    /**
     * @param \Swoole\Http\Server $server
     *
     * @return void
     */
    public function onMasterStart($server)
    {
        @cli_set_process_title(sprintf('manaphp %s: master', $this->config->get('id')));

        $this->fireEvent('httpServer:masterStart', compact('server'));
    }

    /**
     * @return void
     */
    public function onManagerStart()
    {
        @cli_set_process_title(sprintf('manaphp %s: manager', $this->config->get("id")));

        $this->fireEvent('httpServer:managerStart', ['server' => $this->swoole]);
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param int                      $worker_id
     *
     * @return void
     */
    public function onWorkerStart($server, $worker_id)
    {
        @cli_set_process_title(sprintf('manaphp %s: worker/%d', $this->config->get("id"), $worker_id));

        $this->fireEvent('httpServer::workerStart', compact('server', 'worker_id'));
    }

    /**
     * @return void
     */
    public function start()
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            Runtime::enableCoroutine(true);
        }

        echo PHP_EOL, str_repeat('+', 80), PHP_EOL;

        $settings = json_stringify($this->settings);
        console_log('info', ['listen on: %s:%d with setting: %s', $this->host, $this->port, $settings]);
        $this->fireEvent('httpServer:start', ['server' => $this->swoole]);
        $this->swoole->start();
        console_log('info', 'shutdown');
    }

    /**
     * @param \Swoole\Http\Request  $request
     * @param \Swoole\Http\Response $response
     *
     * @return void
     */
    public function onRequest($request, $response)
    {
        if ($request->server['request_uri'] === '/favicon.ico') {
            $response->status(404);
            $response->end();
        } else {
            $context = $this->context;

            $context->response = $response;

            try {
                $this->prepareGlobals($request);

                $this->httpHandler->handle();
            } catch (Throwable $throwable) {
                $str = date('c') . ' ' . get_class($throwable) . ': ' . $throwable->getMessage() . PHP_EOL;
                $str .= '    at ' . $throwable->getFile() . ':' . $throwable->getLine() . PHP_EOL;
                $str .= preg_replace('/#\d+\s/', '    at ', $throwable->getTraceAsString());
                echo $str . PHP_EOL;
            }

            if (!MANAPHP_COROUTINE_ENABLED) {
                global $__root_context;
                foreach ($__root_context as $owner) {
                    unset($owner->context);
                }
                $__root_context = null;
            }
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

        $this->fireEvent('request:responding');

        $response = $this->context->response;

        $response->status($this->response->getStatusCode());

        foreach ($this->response->getHeaders() as $name => $value) {
            $response->header($name, $value, false);
        }

        $response->header('X-Request-Id', $this->request->getRequestId(), false);
        $response->header('X-Response-Time', $this->request->getElapsedTime(), false);

        foreach ($this->response->getCookies() as $cookie) {
            $response->cookie(
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

    public function dump()
    {
        $data = parent::dump();
        unset($data['swoole']);
        return $data;
    }

}
