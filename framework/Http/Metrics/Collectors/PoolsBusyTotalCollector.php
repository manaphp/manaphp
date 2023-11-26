<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Context\ContextorInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\Metrics\CollectorInterface;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Http\Server\Event\RequestEnd;
use ManaPHP\Pooling\Pool\Event\PoolBusy;
use ManaPHP\Pooling\Pool\Event\PoolPopping;
use ManaPHP\Swoole\WorkersTrait;

class PoolsBusyTotalCollector implements CollectorInterface
{
    use WorkersTrait;

    #[Autowired] protected ContextorInterface $contextor;
    #[Autowired] protected FormatterInterface $formatter;

    #[Autowired] protected int $tasker_id = 0;

    protected array $busy_totals = [];
    protected array $pop_totals = [];

    public function getContext(int $cid = 0): PoolsBusyTotalCollectorContext
    {
        return $this->contextor->getContext($this, $cid);
    }

    public function updateRequest(array $busy_pops, array $attempted_pops): void
    {
        foreach ($busy_pops as $owner => $owner_pops) {
            foreach ($owner_pops as $type => $count) {
                if (!isset($this->busy_totals[$owner][$type])) {
                    $this->busy_totals[$owner][$type] = $count;
                } else {
                    $this->busy_totals[$owner][$type] += $count;
                }
            }
        }

        foreach ($attempted_pops as $owner => $owner_pops) {
            foreach ($owner_pops as $type => $count) {
                if (!isset($this->pop_totals[$owner][$type])) {
                    $this->pop_totals[$owner][$type] = $count;
                } else {
                    $this->pop_totals[$owner][$type] += $count;
                }
            }
        }
    }

    public function getResponse(): array
    {
        return [$this->busy_totals, $this->pop_totals];
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

    public function onRequestEnd(#[Event] RequestEnd $event): void
    {
        $context = $this->getContext();

        if ($context->busy_totals !== [] || $context->pop_totals !== []) {
            $this->task($this->tasker_id)->updateRequest($context->busy_totals, $context->pop_totals);
        }
    }

    public function export(): string
    {
        list($busy_totals, $attempted_totals) = $this->task($this->tasker_id, 0.1)->getResponse();

        return $this->formatter->counter('app_pools_busy_total', $busy_totals, [], ['owner', 'type']) .
            $this->formatter->counter('app_pools_pop_total', $attempted_totals, [], ['owner', 'type']);
    }
}