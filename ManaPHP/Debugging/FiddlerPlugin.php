<?php

namespace ManaPHP\Debugging;

use ManaPHP\Event\EventArgs;
use ManaPHP\Logging\Logger;
use ManaPHP\Plugin;

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

class FiddlerPluginContext
{
    /**
     * @var string
     */
    public $channel;

    /**
     * @var array
     */
    public $header;
}

/**
 * @property-read \ManaPHP\Debugging\FiddlerPluginContext $_context
 */
class FiddlerPlugin extends Plugin
{
    /**
     * @var bool
     */
    protected $_watched = true;

    /**
     * @var string
     */
    protected $_prefix;

    /**
     * @var float
     */
    protected $_last_checked;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['redisBroker'])) {
            $this->_injections['redisBroker'] = $options['redisBroker'];
        }

        if (isset($options['pubSub'])) {
            $this->_injections['pubSub'] = $options['pubSub'];
        }

        $context = $this->_context;

        if (MANAPHP_CLI) {
            $context->channel = $this->_prefix . $this->configure->id . ':-';
        }

        $this->_prefix = $options['prefix'] ?? 'broker:fiddlerPlugin:';

        $this->attachEvent('logger:log', [$this, 'onLoggerLog']);
        $this->attachEvent('request:begin', [$this, 'onRequestBegin']);
        $this->attachEvent('response:sent', [$this, 'onResponseSent']);
    }

    /**
     * @return void
     */
    public function onRequestBegin()
    {
        $context = $this->_context;

        $context->channel = $this->_prefix . $this->configure->id . ':' . $this->request->getClientIp();

        $current = microtime(true);
        if ($current - $this->_last_checked >= 1.0) {
            $this->_last_checked = $current;
            $this->_watched = $this->publish('ping', ['timestamp' => round($current, 3)]) > 0;
        }

        if ($this->_watched) {
            $this->logger->setLevel(Logger::LEVEL_DEBUG);

            $server = [];
            foreach ($this->request->getServer() as $k => $v) {
                if (!str_starts_with($k, 'HTTP_')) {
                    continue;
                }
                $server[$k] = $v;
            }

            $this->logger->debug(['_REQUEST' => $this->request->get(), 'SERVER' => $server], 'request.data');
        }
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onLoggerLog(EventArgs $eventArgs)
    {
        /** @var \ManaPHP\Logging\Logger\Log $log */
        $log = $eventArgs->data['log'];

        if ($this->watched()) {
            $this->publish('logger', (array)$log);
        }
    }

    /**
     * @return bool
     */
    public function watched()
    {
        if (MANAPHP_CLI) {
            $current = microtime(true);
            if ($this->_last_checked && $current - $this->_last_checked >= 1.0) {
                $this->_last_checked = $current;
                $this->_watched = $this->publish('ping', ['timestamp' => round($current, 3)]) > 0;
            }
        }

        return $this->_watched;
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onResponseSent(EventArgs $eventArgs)
    {
        /** @var \ManaPHP\Http\ResponseContext $responseContext */
        $responseContext = $eventArgs->data['context'];

        if ($this->watched()) {
            $data = [
                'code'    => $responseContext->status_code,
                'path'    => $this->dispatcher->getPath(),
                'body'    => $responseContext->content,
                'elapsed' => $this->request->getElapsedTime()
            ];
            $this->publish('response', $data);
        }
    }

    /**
     * @param string $type
     * @param array  $data
     *
     * @return int
     */
    public function publish($type, $data)
    {
        $context = $this->_context;

        $packet = [];

        $packet['type'] = $type;
        $packet['data'] = $data;

        $r = $this->redisBroker->call('publish', [$context->channel, json_stringify($packet)]);
        if ($r <= 0) {
            $this->_watched = false;
            $this->_last_checked = microtime(true);
        }

        return $r;
    }

    /**
     * @param array $options
     *
     * @noinspection PhpUnusedParameterInspection
     *
     * @return void
     */
    public function subscribe($options = [])
    {
        $id = $options['id'] ?? $this->configure->id;

        if ($ip = $options['ip'] ?? false) {
            $this->pubSub->subscribe(
                ["{$this->_prefix}$id:$ip"], function ($channel, $packet) {
                $this->processMessage($packet);
            }
            );
        } else {
            $this->pubSub->psubscribe(
                ["{$this->_prefix}$id:*"], function ($channel, $packet) {
                $this->processMessage($packet);
            }
            );
        }
    }

    /**
     * @param string $packet
     *
     * @return void
     * @throws \JsonException
     * @throws \ManaPHP\Exception\JsonException
     */
    public function processMessage($packet)
    {
        $message = json_parse($packet);

        $type = $message['type'];

        if ($type === 'ping') {
            null;
        } elseif ($type === 'response') {
            echo strtr('[path][elapsed][code] body', $message['data']), PHP_EOL;
        } elseif ($type === 'logger') {
            echo $this->process_logger($message['data']), PHP_EOL;
        } else {
            echo json_stringify($message['data']), PHP_EOL;
        }
    }

    /**
     * @param array|object $log
     *
     * @return string
     */
    public function process_logger($log)
    {
        $format = '[:date][:client_ip][:request_id16][:level][:category][:location] :message';
        $replaced = [];

        $log = (object)$log;
        $ms = sprintf('.%03d', ($log->timestamp - (int)$log->timestamp) * 1000);
        $replaced[':date'] = date('Y-m-d\TH:i:s', $log->timestamp) . $ms;
        $replaced[':client_ip'] = $log->client_ip ?: '-';
        $replaced[':request_id'] = $log->request_id ?: '-';
        $replaced[':request_id16'] = $log->request_id ? substr($log->request_id, 0, 16) : '-';
        $replaced[':category'] = $log->category;
        $replaced[':location'] = "$log->file:$log->line";
        $replaced[':level'] = strtoupper($log->level);
        if ($log->category === 'exception') {
            $replaced[':message'] = '';
            /** @noinspection SuspiciousAssignmentsInspection */
            $replaced[':message'] = preg_replace('#[\\r\\n]+#', '\0' . strtr($format, $replaced), $log->message);
        } else {
            $replaced[':message'] = $log->message;
        }

        return strtr($format, $replaced);
    }
}
