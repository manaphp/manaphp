<?php

namespace ManaPHP\Data\Mongodb;

use ManaPHP\Event\EventArgs;

class Tracer extends \ManaPHP\Event\Tracer
{
    public function __construct($options = [])
    {
        parent::__construct($options);

        $this->_verbose && $this->attachEvent('mongodb:connect', [$this, 'onConnect']);
        $this->attachEvent('mongodb:queried', [$this, 'onQueried']);
        $this->attachEvent('mongodb:inserted', [$this, 'onInserted']);
        $this->attachEvent('mongodb:updated', [$this, 'onUpdated']);
        $this->attachEvent('mongodb:deleted', [$this, 'onDeleted']);
        $this->attachEvent('mongodb:commanded', [$this, 'onCommanded']);
        $this->attachEvent('mongodb:bulkInserted', [$this, 'onBulkInserted']);
        $this->attachEvent('mongodb:bulkUpdated', [$this, 'onBulkUpdated']);
        $this->attachEvent('mongodb:upserted', [$this, 'onUpserted']);
        $this->attachEvent('mongodb:bulkUpserted', [$this, 'onBulkUpserted']);
    }

    public function onConnect(EventArgs $eventArgs)
    {
        $this->logger->debug(['connect to `:dsn`', 'dsn' => $eventArgs->data], 'mongodb.connect');
    }

    public function onInserted(EventArgs $eventArgs)
    {
        $this->logger->info($eventArgs->data, 'mongodb.insert');
    }

    public function onBulkInserted(EventArgs $eventArgs)
    {
        $this->logger->info($eventArgs->data, 'mongodb.bulk.insert');
    }

    public function onUpdated(EventArgs $eventArgs)
    {
        $this->logger->info($eventArgs->data, 'mongodb.update');
    }

    public function onUpserted(EventArgs $eventArgs)
    {
        $this->logger->info($eventArgs->data, 'mongodb.upsert');
    }

    public function onBulkUpserted(EventArgs $eventArgs)
    {
        $this->logger->info($eventArgs->data, 'mongodb.bulk.upsert');
    }

    public function onDeleted(EventArgs $eventArgs)
    {
        $this->logger->info($eventArgs->data, 'mongodb.delete');
    }

    public function onQueried(EventArgs $eventArgs)
    {
        $this->logger->debug($eventArgs->data, 'mongodb.query');
    }

    public function onCommanded(EventArgs $eventArgs)
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

    public function onBulkUpdated(EventArgs $eventArgs)
    {
        $this->logger->info($eventArgs->data, 'mongodb.bulk.update');
    }
}