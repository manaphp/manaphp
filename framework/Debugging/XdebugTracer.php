<?php
declare(strict_types=1);

namespace ManaPHP\Debugging;

use ManaPHP\AliasInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Eventing\ListenerProviderInterface;
use ManaPHP\Http\Server\Event\RequestBegin;
use ManaPHP\Http\Server\Event\RequestEnd;

class XdebugTracer implements XdebugTracerInterface
{
    #[Autowired] protected ListenerProviderInterface $listenerProvider;
    #[Autowired] protected AliasInterface $alias;

    #[Autowired] protected int $params = 3;
    #[Autowired] protected int $return = 0;
    #[Autowired] protected int $max_depth = 2;
    #[Autowired] protected int $mem_delta = 1;

    public function start(): void
    {
        ini_set('xdebug.collect_return', (string)$this->return);
        ini_set('xdebug.collect_params', (string)$this->params);
        ini_set('xdebug.var_display_max_depth', (string)$this->max_depth);
        ini_set('xdebug.show_mem_delta', (string)$this->mem_delta);

        $this->listenerProvider->add($this);
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