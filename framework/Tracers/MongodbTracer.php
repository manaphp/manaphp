<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
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
use Psr\Log\LoggerInterface;

class MongodbTracer
{
    #[Inject] protected LoggerInterface $logger;

    #[Value] protected bool $verbose = true;

    public function onConnect(#[Event] MongodbConnect $event): void
    {
        if ($this->verbose) {
            $this->logger->debug('connect to {0}', [$event->uri, 'category' => 'mongodb.connect']);
        }
    }

    public function onInserted(#[Event] MongodbInserted $event): void
    {
        $this->logger->info($event, ['category' => 'mongodb.insert']);
    }

    public function onBulkInserted(#[Event] MongodbBulkInserted $event): void
    {
        $this->logger->info($event, ['category' => 'mongodb.bulk.insert']);
    }

    public function onUpdated(#[Event] MongodbUpdated $event): void
    {
        $this->logger->info($event, ['category' => 'mongodb.update']);
    }

    public function onUpserted(#[Event] MongodbBulkUpserted $event): void
    {
        $this->logger->info($event, ['category' => 'mongodb.upsert']);
    }

    public function onBulkUpserted(#[Event] MongodbBulkUpserted $event): void
    {
        $this->logger->info($event, ['category' => 'mongodb.bulk.upsert']);
    }

    public function onDeleted(#[Event] MongodbDeleted $event): void
    {
        $this->logger->info($event, ['category' => 'mongodb.delete']);
    }

    public function onQueried(#[Event] MongodbQueried $event): void
    {
        $this->logger->debug($event, ['category' => 'mongodb.query']);
    }

    public function onCommanded(#[Event] MongodbCommanded $event): void
    {
        $command_name = key($event->command);

        if (str_contains(
            'ping,aggregate,count,distinct,group,mapReduce,geoNear,geoSearch,find,' .
            'authenticate,listDatabases,listCollections,listIndexes', $command_name
        )
        ) {
            $this->logger->debug($event, ['category' => 'mongodb.command.' . $command_name]);
        } else {
            $this->logger->info($event, ['category' => 'mongodb.command.' . $command_name]);
        }
    }

    public function onBulkUpdated(#[Event] MongodbBulkUpdated $event): void
    {
        $this->logger->info($event, ['category' => 'mongodb.bulk.update']);
    }
}