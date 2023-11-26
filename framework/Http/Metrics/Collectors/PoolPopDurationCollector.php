<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Context\ContextorInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\Metrics\CollectorInterface;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Http\Metrics\Histogram;
use ManaPHP\Http\Server\Event\RequestEnd;
use ManaPHP\Pooling\Pool\Event\PoolPopped;
use ManaPHP\Swoole\WorkersTrait;

class PoolPopDurationCollector implements CollectorInterface
{
    use WorkersTrait;

    #[Autowired] protected ContextorInterface $contextor;
    #[Autowired] protected FormatterInterface $formatter;

    #[Autowired] protected array $buckets = [0.002, 11];
    #[Autowired] protected int $tasker_id = 0;

    protected array $histograms = [];

    public function getContext(int $cid = 0): PoolPopDurationCollectorContext
    {
        return $this->contextor->getContext($this, $cid);
    }

    public function updateRequest(array $pops): void
    {
        foreach ($pops as list($owner, $type, $elapsed)) {
            if (($histogram = $this->histograms[$owner][$type] ?? null) === null) {
                $histogram = $this->histograms[$owner][$type] = new Histogram($this->buckets);
            }
            $histogram->update($elapsed);
        }
    }

    public function getResponse(): array
    {
        return $this->histograms;
    }

    public function onPoolPopped(#[Event] PoolPopped $event): void
    {
        $context = $this->getContext();

        $context->pops[] = [\get_class($event->owner), $event->type, $event->elapsed];
    }

    public function onRequestEnd(#[Event] RequestEnd $event): void
    {
        $context = $this->getContext();

        if ($context->pops !== []) {
            $this->task($this->tasker_id)->updateRequest($context->pops);
        }
    }

    public function export(): string
    {
        $histograms = $this->task($this->tasker_id, 0.1)->getResponse();

        return $this->formatter->histogram('app_pool_pop_duration_seconds', $histograms, [], ['owner', 'type']);
    }
}