<?php

namespace ManaPHP\Data\Mongodb;

use ManaPHP\Event\EventArgs;

class Tracer extends \ManaPHP\Tracing\Tracer
{
    public function __construct($options = [])
    {
        parent::__construct($options);

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

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onConnect(EventArgs $eventArgs)
    {
        $this->debug(['connect to `:dsn`', 'dsn' => $eventArgs->data], 'mongodb.connect');
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onInserted(EventArgs $eventArgs)
    {
        $this->info($eventArgs->data, 'mongodb.insert');
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onBulkInserted(EventArgs $eventArgs)
    {
        $this->info($eventArgs->data, 'mongodb.bulk.insert');
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onUpdated(EventArgs $eventArgs)
    {
        $this->info($eventArgs->data, 'mongodb.update');
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onUpserted(EventArgs $eventArgs)
    {
        $this->info($eventArgs->data, 'mongodb.upsert');
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onBulkUpserted(EventArgs $eventArgs)
    {
        $this->info($eventArgs->data, 'mongodb.bulk.upsert');
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onDeleted(EventArgs $eventArgs)
    {
        $this->info($eventArgs->data, 'mongodb.delete');
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onQueried(EventArgs $eventArgs)
    {
        $this->debug($eventArgs->data, 'mongodb.query');
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onCommanded(EventArgs $eventArgs)
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

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onBulkUpdated(EventArgs $eventArgs)
    {
        $this->info($eventArgs->data, 'mongodb.bulk.update');
    }
}