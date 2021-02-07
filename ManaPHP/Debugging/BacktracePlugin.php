<?php

namespace ManaPHP\Debugging;

use ManaPHP\Plugin;

class BacktracePlugin extends Plugin
{
    /**
     * @var int
     */
    protected $params = 3;

    /**
     * @var int
     */
    protected $return = 0;

    /**
     * @var int
     */
    protected $max_depth = 2;

    /**
     * @var int
     */
    protected $mem_delta = 1;

    /**
     * @param array $options
     */
    public function __construct($options = [])
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

        if (!MANAPHP_CLI && function_exists('xdebug_start_trace')) {
            ini_set('xdebug.collect_return', $this->return);
            ini_set('xdebug.collect_params', $this->params);
            ini_set('xdebug.var_display_max_depth', $this->max_depth);
            ini_set('xdebug.show_mem_delta', $this->mem_delta);

            $this->attachEvent('request:begin', [$this, 'onRequestBegin']);
            $this->attachEvent('request:end', [$this, 'onRequestEnd']);
        }
    }

    /**
     * @return void
     */
    public function onRequestBegin()
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

    /**
     * @return void
     */
    public function onRequestEnd()
    {
        /** @noinspection ForgottenDebugOutputInspection */
        @xdebug_stop_trace();
    }
}