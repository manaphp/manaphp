<?php

namespace ManaPHP\Event;

use ManaPHP\Component;
use ManaPHP\Logging\Logger\LogCategorizable;

class Tracer extends Component implements LogCategorizable
{
    /**
     * @var bool
     */
    protected $verbose = false;

    public function __construct($options = [])
    {
        if (isset($options['verbose'])) {
            $this->verbose = (bool)$options['verbose'];
        }
    }

    public function categorizeLog()
    {
        return str_replace('\\', '.', static::class);
    }
}