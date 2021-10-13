<?php

namespace ManaPHP\Plugins;

class LoggerPluginContext
{
    /**
     * @var bool
     */
    public $enabled = true;

    /**
     * @var string
     */
    public $key;

    /**
     * @var array
     */
    public $logs = [];
}