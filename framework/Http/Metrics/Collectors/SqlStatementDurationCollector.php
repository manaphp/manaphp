<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Context\ContextorInterface;
use ManaPHP\Db\Event\DbExecuted;
use ManaPHP\Db\Event\DbQueried;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\Metrics\CollectorInterface;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Http\Metrics\Histogram;
use ManaPHP\Http\Server\Event\RequestEnd;
use ManaPHP\Swoole\WorkersTrait;

class SqlStatementDurationCollector implements CollectorInterface
{
    use WorkersTrait;

    #[Autowired] protected ContextorInterface $contextor;
    #[Autowired] protected FormatterInterface $formatter;
    #[Autowired] protected DispatcherInterface $dispatcher;

    #[Autowired] protected array $buckets = [0.002, 11];
    #[Autowired] protected int $tasker_id = 0;

    protected array $histograms = [];

    public function getContext(int $cid = 0): SqlStatementDurationCollectorContext
    {
        return $this->contextor->getContext($this, $cid);
    }

    public function updateRequest(string $handler, array $statements): void
    {
        foreach ($statements as list($statement, $elapsed)) {
            if (($histogram = $this->histograms[$handler][$statement] ?? null) === null) {
                $histogram = $this->histograms[$handler][$statement] = new Histogram($this->buckets);
            }
            $histogram->update($elapsed);
        }
    }

    public function getResponse(): array
    {
        return $this->histograms;
    }

    public function onDbQueriedOrDbExecuted(#[Event] DbQueried|DbExecuted $event): void
    {
        $context = $this->getContext();

        $context->statements[] = [$event instanceof DbExecuted ? $event->type : 'select', $event->elapsed];
    }

    public function onRequestEnd(#[Event] RequestEnd $event): void
    {
        if (($handler = $this->dispatcher->getHandler()) !== null) {
            $context = $this->getContext();

            if ($context->statements !== []) {
                $this->task($this->tasker_id)->updateRequest($handler, $context->statements);
            }
        }
    }

    public function export(): string
    {
        $histograms = $this->task($this->tasker_id, 0.1)->getResponse();

        return $this->formatter->histogram('app_sql_statement_duration_seconds', $histograms, [],
            ['handler', 'statement']
        );
    }
}