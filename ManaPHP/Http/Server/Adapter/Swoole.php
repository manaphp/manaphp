<?php

namespace ManaPHP\Http\Server\Adapter;

use ManaPHP\Helper\Ip;
use ManaPHP\Http\Server;
use Swoole\Runtime;
use Throwable;

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

class SwooleContext
{
    /**
     * @var \Swoole\Http\Response
     */
    public $response;
}

/**
 * @property-read \ManaPHP\Http\RouterInterface              $router
 * @property-read \ManaPHP\Http\Server\Adapter\SwooleContext $_context
 */
class Swoole extends Server
{
    /**
     * @var array
     */
    protected $_settings = [];

    /**
     * @var \Swoole\Http\Server
     */
    protected $_swoole;

    /**
     * @var \ManaPHP\Http\Server\HandlerInterface
     */
    protected $_handler;

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
            'SERVER_ADDR'     => $this->_host === '0.0.0.0' ? Ip::local() : $this->_host,
            'SERVER_PORT'     => $this->_port,
            'SERVER_SOFTWARE' => 'Swoole/' . SWOOLE_VERSION . ' (' . PHP_OS . ') PHP/' . PHP_VERSION,
            'PHP_SELF'        => '/' . basename($script_filename),
            'QUERY_STRING'    => '',
            'REQUEST_SCHEME'  => 'http',
        ];

        $this->alias->set('@web', '');
        $this->alias->set('@asset', '');

        $options['enable_coroutine'] = MANAPHP_COROUTINE_ENABLED;

        if (isset($options['max_request']) && $options['max_request'] < 1) {
            $options['max_request'] = 1;
        }

        if (!empty($options['enable_static_handler'])) {
            $options['document_root'] = $this->_SERVER['DOCUMENT_ROOT'];
        }

        parent::__construct($options);

        unset($options['use_globals'], $options['host'], $options['port']);

        $this->_settings = $options;

        if ($this->_use_globals) {
            $this->globalsManager->proxy();
        }

        $this->_swoole = new \Swoole\Http\Server($this->_host, $this->_port);
        $this->_swoole->set($this->_settings);
        $this->_swoole->on('Start', [$this, 'onStart']);
        $this->_swoole->on('ManagerStart', [$this, 'onManagerStart']);
        $this->_swoole->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->_swoole->on('request', [$this, 'onRequest']);
    }

    /**
     * @param \Swoole\Http\Request $request
     *
     * @return void
     */
    protected function _prepareGlobals($request)
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
        $this->request->prepare($_get, $_post, $_server, $raw_body, $request->cookie ?? [], $request->files ?? []);
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     *
     * @noinspection PhpUnusedParameterInspection
     *
     * @return void
     */
    public function onStart($server)
    {
        @cli_set_process_title(sprintf('manaphp %s: master', $this->configure->id));
    }

    /**
     * @return void
     */
    public function onManagerStart()
    {
        @cli_set_process_title(sprintf('manaphp %s: manager', $this->configure->id));
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param int                      $worker_id
     *
     * @noinspection PhpUnusedParameterInspection
     *
     * @return void
     */
    public function onWorkerStart($server, $worker_id)
    {
        @cli_set_process_title(sprintf('manaphp %s: worker/%d', $this->configure->id, $worker_id));
    }

    /**
     * @param \ManaPHP\Http\Server\HandlerInterface $handler
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
        console_log('info', ['listen on: %s:%d with setting: %s', $this->_host, $this->_port, $settings]);
        $this->_swoole->start();
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
            $context = $this->_context;

            $context->response = $response;

            try {
                $this->_prepareGlobals($request);

                $this->_handler->handle();
            } catch (Throwable $throwable) {
                $str = date('c') . ' ' . get_class($throwable) . ': ' . $throwable->getMessage() . PHP_EOL;
                $str .= '    at ' . $throwable->getFile() . ':' . $throwable->getLine() . PHP_EOL;
                $str .= preg_replace('/#\d+\s/', '    at ', $throwable->getTraceAsString());
                echo $str . PHP_EOL;
            }

            if (!MANAPHP_COROUTINE_ENABLED) {
                global $__root_context;
                foreach ($__root_context as $owner) {
                    unset($owner->_context);
                }
                $__root_context = null;
            }
        }
    }

    /**
     * @param \ManaPHP\Http\ResponseContext $response
     *
     * @return void
     */
    public function send($response)
    {
        $this->fireEvent('response:sending', $response);

        $sw_response = $this->_context->response;

        $sw_response->status($response->status_code);

        foreach ($response->headers as $name => $value) {
            $sw_response->header($name, $value, false);
        }

        $sw_response->header('X-Request-Id', $this->request->getRequestId(), false);
        $sw_response->header('X-Response-Time', $this->request->getElapsedTime(), false);

        foreach ($response->cookies as $cookie) {
            $sw_response->cookie(
                $cookie['name'],
                $cookie['value'],
                $cookie['expire'],
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httponly']
            );
        }

        if ($response->status_code === 304) {
            $sw_response->end('');
        } elseif ($this->request->isHead()) {
            $sw_response->header('Content-Length', strlen($response->content), false);
            $sw_response->end('');
        } elseif ($response->file) {
            $sw_response->sendfile($this->alias->resolve($response->file));
        } else {
            $sw_response->end($response->content);
        }

        $this->fireEvent('response:sent', $response);
    }
}
