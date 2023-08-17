<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ArrayObject;
use ManaPHP\Eventing\EventArgs;
use ManaPHP\Tracer;

class DbTracer extends Tracer
{
    public function listen(): void
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

    public function onConnecting(EventArgs $eventArgs): void
    {
        $this->debug(['connecting to `:dsn`', 'dsn' => $eventArgs->data['dsn']], 'db.connect');
    }

    public function onExecuted(EventArgs $eventArgs): void
    {
        $this->info($eventArgs->data, 'db.' . $eventArgs->data['type']);
    }

    public function onQueried(EventArgs $eventArgs): void
    {
        $data = $eventArgs->data instanceof ArrayObject ? $eventArgs->data->getArrayCopy() : $eventArgs->data;

        if (!$this->verbose) {
            unset($data['result']);
        }
        $this->debug($data, 'db.query');
    }

    public function onInserted(EventArgs $eventArgs): void
    {
        $this->info($eventArgs->data, 'db.insert');
    }

    public function onBegin(): void
    {
        $this->info('transaction begin', 'db.begin');
    }

    public function onRollback(): void
    {
        $this->info('transaction rollback', 'db.rollback');
    }

    public function onCommit(): void
    {
        $this->info('transaction commit', 'db.commit');
    }

    public function onMetadata(EventArgs $eventArgs): void
    {
        $this->debug($eventArgs->data, 'db.metadata');
    }

    public function onAbnormal(): void
    {
        $this->error('transaction is not close correctly', 'db.abnormal');
    }
}