<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Context\ContextorInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Http\Metrics\SimpleCollectorInterface;
use ManaPHP\Pooling\Pool\Event\PoolBusy;
use ManaPHP\Pooling\Pool\Event\PoolPopping;

class PoolsBusyTotalCollector implements SimpleCollectorInterface
{
    #[Autowired] protected ContextorInterface $contextor;
    #[Autowired] protected FormatterInterface $formatter;

    protected array $busy_totals = [];
    protected array $pop_totals = [];

    public function getContext(int $cid = 0): PoolsBusyTotalCollectorContext
    {
        return $this->contextor->getContext($this, $cid);
    }

    public function updating(?string $handler): ?array
    {
        $context = $this->getContext();

        if ($context->busy_totals !== [] || $context->pop_totals !== []) {
            return [$context->busy_totals, $context->pop_totals];
        } else {
            return null;
        }
    }

    public function updated(array $data): void
    {
        list($busy_pops, $pop_pops) = $data;

        foreach ($busy_pops as $owner => $owner_pops) {
            foreach ($owner_pops as $type => $count) {
                if (!isset($this->busy_totals[$owner][$type])) {
                    $this->busy_totals[$owner][$type] = $count;
                } else {
                    $this->busy_totals[$owner][$type] += $count;
                }
            }
        }

        foreach ($pop_pops as $owner => $owner_pops) {
            foreach ($owner_pops as $type => $count) {
                if (!isset($this->pop_totals[$owner][$type])) {
                    $this->pop_totals[$owner][$type] = $count;
                } else {
                    $this->pop_totals[$owner][$type] += $count;
                }
            }
        }
    }

    public function onPoolPopping(#[Event] PoolPopping $event): void
    {
        $context = $this->getContext();

        $owner = \get_class($event->owner);
        $type = $event->type;

        if (isset($context->pop_totals[$owner][$type])) {
            $context->pop_totals[$owner][$type]++;
        } else {
            $context->pop_totals[$owner][$type] = 1;
        }
    }

    public function onPoolBusy(#[Event] PoolBusy $event): void
    {
        $context = $this->getContext();

        $owner = \get_class($event->owner);
        $type = $event->type;

        if (isset($context->busy_totals[$owner][$type])) {
            $context->busy_totals[$owner][$type]++;
        } else {
            $context->busy_totals[$owner][$type] = 1;
        }
    }

    public function querying(): array
    {
        return [$this->busy_totals, $this->pop_totals];
    }

    public function export(mixed $data): string
    {
        list($busy_totals, $pop_totals) = $data;

        return $this->formatter->counter('app_pools_busy_total', $busy_totals, [], ['owner', 'type']) .
            $this->formatter->counter('app_pools_pop_total', $pop_totals, [], ['owner', 'type']);
    }
}