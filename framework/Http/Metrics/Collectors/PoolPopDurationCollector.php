<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Context\ContextorInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Http\Metrics\Histogram;
use ManaPHP\Http\Metrics\WorkerCollectorInterface;
use ManaPHP\Pooling\Pool\Event\PoolPopped;
use function get_class;

class PoolPopDurationCollector implements WorkerCollectorInterface
{
    #[Autowired] protected ContextorInterface $contextor;
    #[Autowired] protected FormatterInterface $formatter;

    #[Autowired] protected array $buckets = [0.002, 11];

    protected array $histograms = [];

    public function getContext(int $cid = 0): PoolPopDurationCollectorContext
    {
        return $this->contextor->getContext($this, $cid);
    }

    public function updating(?string $handler): ?array
    {
        $context = $this->getContext();

        return $context->pops !== [] ? [$handler, $context->pops] : null;
    }

    public function updated(mixed $data): void
    {
        list (, $pops) = $data;

        foreach ($pops as list($owner, $type, $elapsed)) {
            if (($histogram = $this->histograms[$owner][$type] ?? null) === null) {
                $histogram = $this->histograms[$owner][$type] = new Histogram($this->buckets);
            }
            $histogram->update($elapsed);
        }
    }

    public function onPoolPopped(#[Event] PoolPopped $event): void
    {
        $context = $this->getContext();

        $context->pops[] = [get_class($event->owner), $event->type, $event->elapsed];
    }

    public function querying(): array
    {
        return $this->histograms;
    }

    public function export(mixed $data): string
    {
        return $this->formatter->histogram('app_pool_pop_duration_seconds', $data, [], ['owner', 'type']);
    }
}