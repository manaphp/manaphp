<?php

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Logging\Logger\LogCategorizable;

class Middleware extends Component implements LogCategorizable
{
    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['enabled'])) {
            $this->enabled = $options['enabled'];
        }

        if ($this->enabled) {
            foreach (get_class_methods($this) as $method) {
                if (str_starts_with($method, 'on')) {
                    $event = lcfirst(substr($method, 2));
                    $this->attachEvent("request:$event", [$this, $method]);
                }
            }
        }
    }

    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', static::class), 'Middleware');
    }
}