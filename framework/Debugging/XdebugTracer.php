<?php
declare(strict_types=1);

namespace ManaPHP\Debugging;

use ManaPHP\Component;

/**
 * @property-read \ManaPHP\AliasInterface $alias
 */
class XdebugTracer extends Component implements XdebugTracerInterface
{
    protected int $params;
    protected int $return;
    protected int $max_depth;
    protected int $mem_delta;

    public function __construct(int $params = 3, int $return = 0, int $max_depth = 2, int $mem_delta = 1)
    {
        $this->params = $params;
        $this->return = $return;
        $this->max_depth = $max_depth;
        $this->mem_delta = $mem_delta;
    }

    public function start(): void
    {
        ini_set('xdebug.collect_return', (string)$this->return);
        ini_set('xdebug.collect_params', (string)$this->params);
        ini_set('xdebug.var_display_max_depth', (string)$this->max_depth);
        ini_set('xdebug.show_mem_delta', (string)$this->mem_delta);

        $this->attachEvent('request:begin', [$this, 'onRequestBegin']);
        $this->attachEvent('request:end', [$this, 'onRequestEnd']);
    }

    public function onRequestBegin(): void
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

    public function onRequestEnd(): void
    {
        /** @noinspection ForgottenDebugOutputInspection */
        @xdebug_stop_trace();
    }
}