<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Context\ContextorInterface;
use ManaPHP\Db\Event\DbBegin;
use ManaPHP\Db\Event\DbCommit;
use ManaPHP\Db\Event\DbRollback;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Http\Metrics\Histogram;
use ManaPHP\Http\Metrics\SimpleCollectorInterface;

class SqlTransactionDurationCollector implements SimpleCollectorInterface
{
    #[Autowired] protected ContextorInterface $contextor;
    #[Autowired] protected FormatterInterface $formatter;

    #[Autowired] protected array $buckets = [0.008, 11];

    protected array $histograms = [];

    public function getContext(int $cid = 0): SqlTransactionDurationCollectorContext
    {
        return $this->contextor->getContext($this, $cid);
    }

    public function updating(?string $handler): ?array
    {
        $context = $this->getContext();

        return ($handler !== null && $context->transactions !== []) ? [$handler, $context->transactions] : null;
    }

    public function updated(array $data): void
    {
        list($handler, $transactions) = $data;

        foreach ($transactions as list($type, $elapsed)) {
            if (($histogram = $this->histograms[$handler][$type] ?? null) === null) {
                $histogram = $this->histograms[$handler][$type] = new Histogram($this->buckets);
            }
            $histogram->update($elapsed);
        }
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

    public function querying(): array
    {
        return $this->histograms;
    }

    public function export(mixed $data): string
    {
        return $this->formatter->histogram('app_sql_transaction_duration_seconds', $data, [], ['handler', 'type']);
    }
}