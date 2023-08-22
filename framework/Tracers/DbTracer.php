<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\Db\Event\DbAbnormal;
use ManaPHP\Db\Event\DbBegin;
use ManaPHP\Db\Event\DbCommit;
use ManaPHP\Db\Event\DbConnecting;
use ManaPHP\Db\Event\DbExecuted;
use ManaPHP\Db\Event\DbMetadata;
use ManaPHP\Db\Event\DbQueried;
use ManaPHP\Db\Event\DbRollback;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Tracer;

class DbTracer extends Tracer
{
    public function onConnecting(#[Event] DbConnecting $event): void
    {
        if ($this->verbose) {
            $this->debug(['connecting to `:dsn`', 'dsn' => $event->dsn], 'db.connect');
        }
    }

    public function onExecuted(#[Event] DbExecuted $event): void
    {
        $this->info($event->sql, 'db.' . $event->type);
    }

    public function onQueried(#[Event] DbQueried $event): void
    {
        $this->debug($event->sql, 'db.query');
    }

    public function onInserted(#[Event] DbQueried $event): void
    {
        $this->info($event, 'db.insert');
    }

    public function onBegin(#[Event] DbBegin $event): void
    {
        $this->info('transaction begin', 'db.begin');
    }

    public function onRollback(#[Event] DbRollback $eent): void
    {
        $this->info('transaction rollback', 'db.rollback');
    }

    public function onCommit(#[Event] DbCommit $event): void
    {
        $this->info('transaction commit', 'db.commit');
    }

    public function onMetadata(#[Event] DbMetadata $event): void
    {
        if ($this->verbose) {
            $this->debug($event, 'db.metadata');
        }
    }

    public function onAbnormal(#[Event] DbAbnormal $event): void
    {
        $this->error('transaction is not close correctly', 'db.abnormal');
    }
}