<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\Event\EventArgs;
use ManaPHP\Tracer;

class MongodbTracer extends Tracer
{
    public function listen(): void
    {
        $this->verbose && $this->attachEvent('mongodb:connect', [$this, 'onConnect']);
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

    public function onConnect(EventArgs $eventArgs): void
    {
        $this->debug(['connect to `:dsn`', 'dsn' => $eventArgs->data], 'mongodb.connect');
    }

    public function onInserted(EventArgs $eventArgs): void
    {
        $this->info($eventArgs->data, 'mongodb.insert');
    }

    public function onBulkInserted(EventArgs $eventArgs): void
    {
        $this->info($eventArgs->data, 'mongodb.bulk.insert');
    }

    public function onUpdated(EventArgs $eventArgs): void
    {
        $this->info($eventArgs->data, 'mongodb.update');
    }

    public function onUpserted(EventArgs $eventArgs): void
    {
        $this->info($eventArgs->data, 'mongodb.upsert');
    }

    public function onBulkUpserted(EventArgs $eventArgs): void
    {
        $this->info($eventArgs->data, 'mongodb.bulk.upsert');
    }

    public function onDeleted(EventArgs $eventArgs): void
    {
        $this->info($eventArgs->data, 'mongodb.delete');
    }

    public function onQueried(EventArgs $eventArgs): void
    {
        $this->debug($eventArgs->data, 'mongodb.query');
    }

    public function onCommanded(EventArgs $eventArgs): void
    {
        $command_name = key($eventArgs->data['command']);

        if (str_contains(
            'ping,aggregate,count,distinct,group,mapReduce,geoNear,geoSearch,find,' .
            'authenticate,listDatabases,listCollections,listIndexes', $command_name
        )
        ) {
            $this->debug($eventArgs->data, 'mongodb.command.' . $command_name);
        } else {
            $this->info($eventArgs->data, 'mongodb.command.' . $command_name);
        }
    }

    public function onBulkUpdated(EventArgs $eventArgs): void
    {
        $this->info($eventArgs->data, 'mongodb.bulk.update');
    }
}