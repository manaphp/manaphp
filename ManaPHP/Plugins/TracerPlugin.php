<?php

namespace ManaPHP\Plugins;

use ManaPHP\Event\EventArgs;
use ManaPHP\Plugin;

/**
 * Class TracerPlugin
 *
 * @package ManaPHP\Plugins
 */
class TracerPlugin extends Plugin
{
    /**
     * @var bool
     */
    protected $_verbose = false;

    /**
     * TracerPlugin constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['verbose'])) {
            $this->_verbose = (bool)$options['verbose'];
        }

        $verbose = $this->_verbose;

        $verbose && $this->attachEvent('redis:connect', [$this, 'onRedisConnect']);
        $this->attachEvent('redis:calling', [$this, 'onRedisCalling']);
        $this->attachEvent('redis:called', [$this, 'onRedisCalled']);

        $verbose && $this->attachEvent('db:connect', [$this, 'onDbConnect']);
        $this->attachEvent('db:queried', [$this, 'onDbQueried']);
        $this->attachEvent('db:executed', [$this, 'onDbExecuted']);
        $this->attachEvent('db:inserted', [$this, 'onDbInserted']);
        $this->attachEvent('db:begin', [$this, 'onDbBegin']);
        $this->attachEvent('db:rollback', [$this, 'onDbRollback']);
        $this->attachEvent('db:commit', [$this, 'onDbCommit']);
        $verbose && $this->attachEvent('db:metadata', [$this, 'onDbMetadata']);
        $this->attachEvent('db:abnormal', [$this, 'onDbAbnormal']);

        $this->attachEvent('mailer:sending', [$this, 'onMailerSending']);

        $verbose && $this->attachEvent('mongodb:connect', [$this, 'onMongodbConnect']);
        $this->attachEvent('mongodb:queried', [$this, 'onMongodbQueried']);
        $this->attachEvent('mongodb:inserted', [$this, 'onMongodbInserted']);
        $this->attachEvent('mongodb:updated', [$this, 'onMongodbUpdated']);
        $this->attachEvent('mongodb:deleted', [$this, 'onMongodbDeleted']);
        $this->attachEvent('mongodb:commanded', [$this, 'onMongodbCommanded']);
        $this->attachEvent('mongodb:bulkInserted', [$this, 'onMongodbBulkInserted']);
        $this->attachEvent('mongodb:bulkUpdated', [$this, 'onMongodbBulkUpdated']);
        $this->attachEvent('mongodb:upserted', [$this, 'onMongodbUpserted']);
        $this->attachEvent('mongodb:bulkUpserted', [$this, 'onMongodbBulkUpserted']);

        $this->attachEvent('httpClient:requesting', [$this, 'onHttpClientRequesting']);
        $this->attachEvent('httpClient:requested', [$this, 'onHttpClientRequested']);

        $verbose && $this->attachEvent('wsClient:send', [$this, 'onWsClientSend']);
        $verbose && $this->attachEvent('wsClient:recv', [$this, 'onWsClientRecv']);
    }

    public function onRedisConnect(EventArgs $eventArgs)
    {
        $this->logger->debug(['connect to `:url`', 'url' => $eventArgs->data['url']], 'redis.connect');
    }

    public function onRedisCalling(EventArgs $eventArgs)
    {
        $method = $eventArgs->data['method'];
        $arguments = $eventArgs->data['arguments'];

        if (stripos(',blPop,brPop,brpoplpush,subscribe,psubscribe,', ",$method,") !== false) {
            $this->logger->debug(
                [
                    "\$redis->$method(:args) ... blocking",
                    'args' => substr(json_stringify($arguments, JSON_PARTIAL_OUTPUT_ON_ERROR), 1, -1),
                ], 'redis.' . $method
            );
        }
    }

    public function onRedisCalled(EventArgs $eventArgs)
    {
        $method = $eventArgs->data['method'];
        $arguments = $eventArgs->data['arguments'];
        foreach ($arguments as $k => $v) {
            if (is_string($v) && strlen($v) > 128) {
                $arguments[$k] = substr($v, 0, 128) . '...';
            }
        }

        if ($this->_verbose) {
            $arguments = json_stringify($arguments, JSON_PARTIAL_OUTPUT_ON_ERROR);
            $return = json_stringify($eventArgs->data['return'], JSON_PARTIAL_OUTPUT_ON_ERROR);
            $this->logger->debug(
                [
                    "\$redis->$method(:args) => :return",
                    'args'   => strlen($arguments) > 256
                        ? substr($arguments, 1, 256) . '...)'
                        : substr(
                            $arguments, 1, -1
                        ),
                    'return' => strlen($return) > 64 ? substr($return, 0, 64) . '...' : $return
                ], 'redis.' . $method
            );
        } else {
            $key = $arguments[0] ?? false;
            if (!$this->configure->debug && is_string($key) && str_starts_with($key, 'cache:')) {
                return;
            }
            $arguments = json_stringify($arguments, JSON_PARTIAL_OUTPUT_ON_ERROR);
            $this->logger->debug(
                [
                    "\$redis->$method(:args)",
                    'args' => strlen($arguments) > 256
                        ? substr($arguments, 1, 256) . '...)'
                        : substr(
                            $arguments, 1, -1
                        ),
                ], 'redis.' . $method
            );
        }

    }

    public function onDbConnect(EventArgs $eventArgs)
    {
        $this->logger->debug(['connect to `:dsn`', 'dsn' => $eventArgs->data['dsn']], 'db.connect');
    }

    public function onDbExecuted(EventArgs $eventArgs)
    {
        $data = $eventArgs->data;

        $this->logger->info($data, 'db.' . $data['type']);
    }

    public function onDbQueried(EventArgs $eventArgs)
    {
        $data = $eventArgs->data;

        if (!$this->_verbose) {
            unset($data['result']);
        }
        $this->logger->debug($data, 'db.query');
    }

    public function onDbInserted(EventArgs $eventArgs)
    {
        $this->logger->info($eventArgs->data, 'db.insert');
    }

    public function onDbBegin()
    {
        $this->logger->info('transaction begin', 'db.begin');
    }

    public function onDbRollback()
    {
        $this->logger->info('transaction rollback', 'db.rollback');
    }

    public function onDbCommit()
    {
        $this->logger->info('transaction commit', 'db.commit');
    }

    public function onDbMetadata(EventArgs $eventArgs)
    {
        $this->logger->debug($eventArgs->data, 'db.metadata');
    }

    public function onDbAbnormal()
    {
        $this->logger->error('transaction is not close correctly', 'db.abnormal');
    }

    public function onMailerSending(EventArgs $eventArgs)
    {
        /** @var \ManaPHP\Mailer\Message $message */
        $message = $eventArgs->data['message'];

        if ($this->_verbose) {
            $this->logger->debug(['From: ', $message->getFrom()]);
            $this->logger->debug(['To: ', $message->getTo()]);
            $this->logger->debug(['Cc:', $message->getCc()]);
            $this->logger->debug(['Bcc: ', $message->getBcc()]);
            $this->logger->debug(['Subject: ', $message->getSubject()]);
        } else {
            $this->logger->debug(['To: ', $message->getTo()]);
        }
    }

    public function onMongodbConnect(EventArgs $eventArgs)
    {
        $this->logger->debug(['connect to `:dsn`', $eventArgs->data], 'mongodb.connect');
    }

    public function onMongodbInserted(EventArgs $eventArgs)
    {
        $this->logger->info($eventArgs->data, 'mongodb.insert');
    }

    public function onMongodbBulkInserted(EventArgs $eventArgs)
    {
        $this->logger->info($eventArgs->data, 'mongodb.bulk.insert');
    }

    public function onMongodbUpdated(EventArgs $eventArgs)
    {
        $this->logger->info($eventArgs->data, 'mongodb.update');
    }

    public function onMongodbUpserted(EventArgs $eventArgs)
    {
        $this->logger->info($eventArgs->data, 'mongodb.upsert');
    }

    public function onMongodbBulkUpserted(EventArgs $eventArgs)
    {
        $this->logger->info($eventArgs->data, 'mongodb.bulk.upsert');
    }

    public function onMongodbDeleted(EventArgs $eventArgs)
    {
        $this->logger->info($eventArgs->data, 'mongodb.delete');
    }

    public function onMongodbQueried(EventArgs $eventArgs)
    {
        $this->logger->debug($eventArgs->data, 'mongodb.query');
    }

    public function onMongodbCommanded(EventArgs $eventArgs)
    {
        $data = $eventArgs->data;

        $command_name = key($data['command']);
        if (str_contains(
            'ping,aggregate,count,distinct,group,mapReduce,geoNear,geoSearch,find,' .
            'authenticate,listDatabases,listCollections,listIndexes', $command_name
        )
        ) {
            $this->logger->debug($data, 'mongodb.command.' . $command_name);
        } else {
            $this->logger->info($data, 'mongodb.command.' . $command_name);
        }
    }

    public function onMongodbBulkUpdated(EventArgs $eventArgs)
    {
        $this->logger->info($eventArgs->data, 'mongodb.bulk.update');
    }

    public function onHttpClientRequesting(EventArgs $eventArgs)
    {
        /** @var \ManaPHP\Http\Client\Request $request */
        $request = $eventArgs->data;

        if ($request->method === 'POST' && $request->body) {
            $this->logger->info($eventArgs->data, 'httpClient.request');
        }
    }

    public function onHttpClientRequested(EventArgs $eventArgs)
    {
        /** @var \ManaPHP\Http\Client\Response $response */
        $response = clone $eventArgs->data;

        if (!$this->_verbose) {
            unset($response->stats, $response->headers);
        }

        $this->logger->debug($response, 'httpClient.response');
    }

    public function onWsClientSend(EventArgs $eventArgs)
    {
        $this->logger->debug($eventArgs->data, 'wsClient.send');
    }

    public function onWsClientRecv(EventArgs $eventArgs)
    {
        $this->logger->debug($eventArgs->data, 'wsClient.recv');
    }
}