<?php
declare(strict_types=1);

namespace ManaPHP\Debugging;

use ManaPHP\AliasInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Eventing\EventSubscriberInterface;
use ManaPHP\Http\Server\Event\RequestBegin;
use ManaPHP\Http\Server\Event\RequestEnd;

class XdebugTracer implements XdebugTracerInterface
{
    #[Inject] protected EventSubscriberInterface $eventSubscriber;
    #[Inject] protected AliasInterface $alias;

    #[Value] protected int $params = 3;
    #[Value] protected int $return = 0;
    #[Value] protected int $max_depth = 2;
    #[Value] protected int $mem_delta = 1;

    public function start(): void
    {
        ini_set('xdebug.collect_return', (string)$this->return);
        ini_set('xdebug.collect_params', (string)$this->params);
        ini_set('xdebug.var_display_max_depth', (string)$this->max_depth);
        ini_set('xdebug.show_mem_delta', (string)$this->mem_delta);

        $this->eventSubscriber->addListener($this);
    }

    public function onRequestBegin(#[Event] RequestBegin $event): void
    {
        $file = $this->alias->resolve('@runtime/backtracePlugin/trace_{ymd_His}_{8}.log');
        $dir = dirname($file);
        if (!is_dir($dir)) {
            /** @noinspection MkdirRaceConditionInspection */
            @mkdir($dir, 0777, true);
        }

        /** @noinspection ForgottenDebugOutputInspection */
        xdebug_start_trace($file);
    }

    public function onRequestEnd(#[Event] RequestEnd $event): void
    {
        /** @noinspection ForgottenDebugOutputInspection */
        @xdebug_stop_trace();
    }
}