<?php

namespace ManaPHP\Logging;

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