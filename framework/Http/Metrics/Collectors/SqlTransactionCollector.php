<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Context\ContextorInterface;
use ManaPHP\Db\Event\DbBegin;
use ManaPHP\Db\Event\DbCommit;
use ManaPHP\Db\Event\DbRollback;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\Metrics\CollectorInterface;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Http\Metrics\Histogram;
use ManaPHP\Http\Server\Event\RequestEnd;
use ManaPHP\Swoole\WorkersTrait;

class SqlTransactionCollector implements CollectorInterface
{
    use WorkersTrait;

    #[Autowired] protected ContextorInterface $contextor;
    #[Autowired] protected FormatterInterface $formatter;
    #[Autowired] protected DispatcherInterface $dispatcher;

    #[Autowired] protected array $buckets = [0.008, 11];
    #[Autowired] protected int $tasker_id = 0;

    protected array $histograms = [];

    public function getContext(int $cid = 0): SqlTransactionCollectorContext
    {
        return $this->contextor->getContext($this, $cid);
    }

    public function updateRequest(string $handler, array $transactions): void
    {
        foreach ($transactions as list($type, $elapsed)) {
            if (($histogram = $this->histograms[$handler][$type] ?? null) === null) {
                $histogram = $this->histograms[$handler][$type] = new Histogram($this->buckets);
            }
            $histogram->update($elapsed);
        }
    }

    public function getResponse(): array
    {
        return $this->histograms;
    }

    public function onDbBegin(#[Event] DbBegin $event): void
    {
        $context = $this->getContext();
        $context->start_time = \microtime(true);
    }

    public function onDbCommit(#[Event] DbCommit $event): void
    {
        $context = $this->getContext();
        $context->transactions[] = ['commit', \microtime(true) - $context->start_time];
    }

    public function onDbRollback(#[Event] DbRollback $event): void
    {
        $context = $this->getContext();
        $context->transactions[] = ['rollback', \microtime(true) - $context->start_time];
    }

    public function onRequestEnd(#[Event] RequestEnd $event): void
    {
        if (($handler = $this->dispatcher->getHandler()) !== null) {
            $context = $this->getContext();

            if ($context->transactions !== []) {
                $this->task($this->tasker_id)->updateRequest($handler, $context->transactions);
            }
        }
    }

    public function export(): string
    {
        $histograms = $this->task($this->tasker_id, 0.1)->getResponse();

        return $this->formatter->histogram('app_sql_transaction_duration_seconds', $histograms, [],
            ['handler', 'type']
        );
    }
}