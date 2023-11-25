<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Context\ContextTrait;
use ManaPHP\Db\Event\DbExecuted;
use ManaPHP\Db\Event\DbExecuting;
use ManaPHP\Db\Event\DbQueried;
use ManaPHP\Db\Event\DbQuerying;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\Metrics\CollectorInterface;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Http\Metrics\Histogram;
use ManaPHP\Http\Server\Event\RequestEnd;
use ManaPHP\Swoole\WorkersTrait;

class SqlStatementCollector implements CollectorInterface
{
    use ContextTrait;
    use WorkersTrait;

    #[Autowired] protected FormatterInterface $formatter;
    #[Autowired] protected DispatcherInterface $dispatcher;

    #[Autowired] protected array $buckets = ['0.001', '0.005', '0.01', '0.05', '0.1', '0.5', '1'];

    protected array $histograms = [];

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

    public function onDbQueryingOrDbExecuting(#[Event] DbQuerying|DbExecuting $event): void
    {
        /** @var SqlStatementCollectorContext $context */
        $context = $this->getContext();
        $context->statement = $event instanceof DbExecuting ? $event->type : 'select';
        $context->start_time = \microtime(true);
    }

    public function onDbQueriedOrDbExecuted(#[Event] DbQueried|DbExecuted $event): void
    {
        /** @var SqlStatementCollectorContext $context */
        $context = $this->getContext();

        $context->statements[] = [$context->statement, \microtime(true) - $context->start_time];
    }

    public function onRequestEnd(#[Event] RequestEnd $event): void
    {
        if (($handler = $this->dispatcher->getHandler()) !== null) {
            /** @var SqlStatementCollectorContext $context */
            $context = $this->getContext();

            $this->task(0)->updateRequest($handler, $context->statements);
        }
    }

    public function export(): string
    {
        $histograms = $this->task(0, 1.0)->getResponse();

        return $this->formatter->histogram('app_sql_statement_duration_seconds', $histograms, [],
            ['handler', 'statement']
        );
    }
}