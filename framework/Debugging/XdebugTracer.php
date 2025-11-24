<?php

declare(strict_types=1);

namespace ManaPHP\Debugging;

use ManaPHP\Alias\Path;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Eventing\ListenerProviderInterface;
use ManaPHP\Helper\SuppressWarnings;
use ManaPHP\Http\Event\RequestBegin;
use ManaPHP\Http\Event\RequestEnd;
use function bin2hex;
use function dirname;
use function ini_set;
use function is_dir;
use function mkdir;
use function random_bytes;
use function xdebug_start_trace;
use function xdebug_stop_trace;

class XdebugTracer implements XdebugTracerInterface
{
    #[Autowired] protected ListenerProviderInterface $listenerProvider;

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
        SuppressWarnings::unused($event);

        $file = Path::resolve('@runtime/backtrace/trace_{date}_{rand}.log', [
            'date' => date('ymd_His'),
            'rand' => bin2hex(random_bytes(4))
        ]);

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
        SuppressWarnings::unused($event);

        /** @noinspection ForgottenDebugOutputInspection */
        @xdebug_stop_trace();
    }
}
