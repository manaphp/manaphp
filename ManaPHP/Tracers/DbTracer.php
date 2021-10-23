<?php

namespace ManaPHP\Tracers;

use ArrayObject;
use ManaPHP\Event\EventArgs;
use ManaPHP\Tracer;

class DbTracer extends Tracer
{
    public function listen()
    {
        $this->verbose && $this->attachEvent('db:connecting', [$this, 'onConnecting']);
        $this->attachEvent('db:queried', [$this, 'onQueried']);
        $this->attachEvent('db:executed', [$this, 'onExecuted']);
        $this->attachEvent('db:inserted', [$this, 'onInserted']);
        $this->attachEvent('db:begin', [$this, 'onBegin']);
        $this->attachEvent('db:rollback', [$this, 'onRollback']);
        $this->attachEvent('db:commit', [$this, 'onCommit']);
        $this->verbose && $this->attachEvent('db:metadata', [$this, 'onMetadata']);
        $this->attachEvent('db:abnormal', [$this, 'onAbnormal']);
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onConnecting(EventArgs $eventArgs)
    {
        $this->debug(['connecting to `:dsn`', 'dsn' => $eventArgs->data['dsn']], 'db.connect');
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onExecuted(EventArgs $eventArgs)
    {
        $this->info($eventArgs->data, 'db.' . $eventArgs->data['type']);
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onQueried(EventArgs $eventArgs)
    {
        $data = $eventArgs->data instanceof ArrayObject ? $eventArgs->data->getArrayCopy() : $eventArgs->data;

        if (!$this->verbose) {
            unset($data['result']);
        }
        $this->debug($data, 'db.query');
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onInserted(EventArgs $eventArgs)
    {
        $this->info($eventArgs->data, 'db.insert');
    }

    /**
     * @return void
     */
    public function onBegin()
    {
        $this->info('transaction begin', 'db.begin');
    }

    /**
     * @return void
     */
    public function onRollback()
    {
        $this->info('transaction rollback', 'db.rollback');
    }

    /**
     * @return void
     */
    public function onCommit()
    {
        $this->info('transaction commit', 'db.commit');
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onMetadata(EventArgs $eventArgs)
    {
        $this->debug($eventArgs->data, 'db.metadata');
    }

    /**
     * @return void
     */
    public function onAbnormal()
    {
        $this->error('transaction is not close correctly', 'db.abnormal');
    }
}