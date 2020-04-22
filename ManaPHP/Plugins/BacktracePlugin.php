<?php

namespace ManaPHP\Plugins;

use ManaPHP\Plugin;

class BacktracePlugin extends Plugin
{
    /**
     * @var int
     */
    protected $_params = 3;

    /**
     * @var int
     */
    protected $_return = 0;

    /**
     * @var int
     */
    protected $_max_depth = 2;

    /**
     * @var int
     */
    protected $_mem_delta = 1;

    /**
     * BacktracePlugin constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['params'])) {
            $this->_params = $options['params'];
        }

        if (isset($options['return'])) {
            $this->_return = $options['return'];
        }

        if (isset($options['max_depth'])) {
            $this->_max_depth = $options['max_depth'];
        }

        if (isset($options['mem_delta'])) {
            $this->_mem_delta = $options['mem_delta'];
        }

        if (!MANAPHP_CLI && function_exists('xdebug_start_trace')) {
            ini_set('xdebug.collect_return', $this->_return);
            ini_set('xdebug.collect_params', $this->_params);
            ini_set('xdebug.var_display_max_depth', $this->_max_depth);
            ini_set('xdebug.show_mem_delta', $this->_mem_delta);

            $this->attachEvent('request:begin', [$this, 'onRequestBegin']);
            $this->attachEvent('request:end', [$this, 'onRequestEnd']);
        }
    }

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

    public function onRequestEnd()
    {
        /** @noinspection ForgottenDebugOutputInspection */
        @xdebug_stop_trace();
    }
}