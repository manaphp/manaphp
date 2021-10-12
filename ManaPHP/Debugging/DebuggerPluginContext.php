<?php

namespace ManaPHP\Debugging;

class DebuggerPluginContext
{
    /**
     * @var bool
     */
    public $enabled;

    /**
     * @var string
     */
    public $key;

    /**
     * @var array
     */
    public $view = [];

    /**
     * @var array
     */
    public $log = [];

    /**
     * @var array
     */
    public $sql_prepared = [];

    /**
     * @var array
     */
    public $sql_executed = [];

    /**
     * @var int
     */
    public $sql_count = 0;

    /**
     * @var array
     */
    public $mongodb = [];

    /**
     * @var array
     */
    public $events = [];
}