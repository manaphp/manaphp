<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\Db\Event\DbAbnormal;
use ManaPHP\Db\Event\DbBegin;
use ManaPHP\Db\Event\DbCommit;
use ManaPHP\Db\Event\DbConnecting;
use ManaPHP\Db\Event\DbExecuted;
use ManaPHP\Db\Event\DbInserted;
use ManaPHP\Db\Event\DbMetadata;
use ManaPHP\Db\Event\DbQueried;
use ManaPHP\Db\Event\DbRollback;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Eventing\Attribute\Event;
use Psr\Log\LoggerInterface;

class DbTracer
{
    #[Inject] protected LoggerInterface $logger;

    #[Value] protected bool $verbose = true;

    public function onConnecting(#[Event] DbConnecting $event): void
    {
        if ($this->verbose) {
            $this->logger->debug('connecting to {0}', [$event->dsn, 'category' => 'db.connect']);
        }
    }

    public function onExecuted(#[Event] DbExecuted $event): void
    {
        $this->logger->info($event, ['category' => 'db.' . $event->type]);
    }

    public function onQueried(#[Event] DbQueried $event): void
    {
        $this->logger->debug($event, ['category' => 'db.query']);
    }

    public function onInserted(#[Event] DbInserted $event): void
    {
        $this->logger->info($event, ['category' => 'db.insert']);
    }

    public function onBegin(#[Event] DbBegin $event): void
    {
        $this->logger->info('transaction begin', ['category' => 'db.begin']);
    }

    public function onRollback(#[Event] DbRollback $event): void
    {
        $this->logger->info('transaction rollback', ['category' => 'db.rollback']);
    }

    public function onCommit(#[Event] DbCommit $event): void
    {
        $this->logger->info('transaction commit', ['category' => 'db.commit']);
    }

    public function onMetadata(#[Event] DbMetadata $event): void
    {
        if ($this->verbose) {
            $this->logger->debug($event, ['category' => 'db.metadata']);
        }
    }

    public function onAbnormal(#[Event] DbAbnormal $event): void
    {
        $this->logger->error('transaction is not close correctly', ['category' => 'db.abnormal']);
    }
}