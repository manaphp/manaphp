<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Context\ContextManagerInterface;
use ManaPHP\Db\Event\DbExecuted;
use ManaPHP\Db\Event\DbQueried;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Http\Metrics\Histogram;
use ManaPHP\Http\Metrics\WorkerCollectorInterface;

class SqlStatementDurationCollector implements WorkerCollectorInterface
{
    #[Autowired] protected ContextManagerInterface $contextManager;
    #[Autowired] protected FormatterInterface $formatter;

    #[Autowired] protected array $buckets = [0.002, 11];

    protected array $histograms = [];

    public function getContext(int $cid = 0): SqlStatementDurationCollectorContext
    {
        return $this->contextManager->getContext($this, $cid);
    }

    public function updating(?string $handler): ?array
    {
        $context = $this->getContext();

        return ($handler !== null && $context->statements !== []) ? [$handler, $context->statements] : null;
    }

    public function updated(array $data): void
    {
        list($handler, $statements) = $data;
        foreach ($statements as list($statement, $elapsed)) {
            if (($histogram = $this->histograms[$handler][$statement] ?? null) === null) {
                $histogram = $this->histograms[$handler][$statement] = new Histogram($this->buckets);
            }
            $histogram->update($elapsed);
        }
    }

    public function onDbQueriedOrDbExecuted(#[Event] DbQueried|DbExecuted $event): void
    {
        $context = $this->getContext();

        $context->statements[] = [$event instanceof DbExecuted ? $event->type : 'select', $event->elapsed];
    }

    public function querying(): array
    {
        return $this->histograms;
    }

    public function export(mixed $data): string
    {
        return $this->formatter->histogram('app_sql_statement_duration_seconds', $data, [], ['handler', 'statement']);
    }
}