<?php
declare(strict_types=1);

namespace ManaPHP\Debugging;

use ManaPHP\Component;

/**
 * @property-read \ManaPHP\AliasInterface $alias
 */
class XdebugTracer extends Component implements XdebugTracerInterface
{
    protected int $params = 3;
    protected int $return = 0;
    protected int $max_depth = 2;
    protected int $mem_delta = 1;

    public function __construct(array $options = [])
    {
        if (isset($options['params'])) {
            $this->params = $options['params'];
        }

        if (isset($options['return'])) {
            $this->return = $options['return'];
        }

        if (isset($options['max_depth'])) {
            $this->max_depth = $options['max_depth'];
        }

        if (isset($options['mem_delta'])) {
            $this->mem_delta = $options['mem_delta'];
        }
    }

    public function start(): void
    {
        ini_set('xdebug.collect_return', $this->return);
        ini_set('xdebug.collect_params', $this->params);
        ini_set('xdebug.var_display_max_depth', $this->max_depth);
        ini_set('xdebug.show_mem_delta', $this->mem_delta);

        $this->attachEvent('request:begin', [$this, 'onRequestBegin']);
        $this->attachEvent('request:end', [$this, 'onRequestEnd']);
    }

    public function onRequestBegin(): void
    {
        $file = $this->alias->resolve('@data/backtracePlugin/trace_{ymd_His}_{8}.log');
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