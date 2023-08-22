<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Mongodb\Event\MongodbBulkInserted;
use ManaPHP\Mongodb\Event\MongodbBulkUpdated;
use ManaPHP\Mongodb\Event\MongodbBulkUpserted;
use ManaPHP\Mongodb\Event\MongodbCommanded;
use ManaPHP\Mongodb\Event\MongodbConnect;
use ManaPHP\Mongodb\Event\MongodbDeleted;
use ManaPHP\Mongodb\Event\MongodbInserted;
use ManaPHP\Mongodb\Event\MongodbQueried;
use ManaPHP\Mongodb\Event\MongodbUpdated;
use ManaPHP\Tracer;

class MongodbTracer extends Tracer
{
    public function onConnect(#[Event] MongodbConnect $event): void
    {
        if ($this->verbose) {
            $this->debug(['connect to `:dsn`', 'dsn' => $event->uri], 'mongodb.connect');
        }
    }

    public function onInserted(#[Event] MongodbInserted $event): void
    {
        $this->info($event->document, 'mongodb.insert');
    }

    public function onBulkInserted(#[Event] MongodbBulkInserted $event): void
    {
        $this->info($event->documents, 'mongodb.bulk.insert');
    }

    public function onUpdated(#[Event] MongodbUpdated $event): void
    {
        $this->info($event->document, 'mongodb.update');
    }

    public function onUpserted(#[Event] MongodbBulkUpserted $event): void
    {
        $this->info($event->documents, 'mongodb.upsert');
    }

    public function onBulkUpserted(#[Event] MongodbBulkUpserted $event): void
    {
        $this->info($event, 'mongodb.bulk.upsert');
    }

    public function onDeleted(#[Event] MongodbDeleted $event): void
    {
        $this->info($event, 'mongodb.delete');
    }

    public function onQueried(#[Event] MongodbQueried $event): void
    {
        $this->debug($event, 'mongodb.query');
    }

    public function onCommanded(#[Event] MongodbCommanded $event): void
    {
        $command_name = key($event->command);

        if (str_contains(
            'ping,aggregate,count,distinct,group,mapReduce,geoNear,geoSearch,find,' .
            'authenticate,listDatabases,listCollections,listIndexes', $command_name
        )
        ) {
            $this->debug($event, 'mongodb.command.' . $command_name);
        } else {
            $this->info($event, 'mongodb.command.' . $command_name);
        }
    }

    public function onBulkUpdated(#[Event] MongodbBulkUpdated $event): void
    {
        $this->info($event, 'mongodb.bulk.update');
    }
}