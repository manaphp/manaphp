<?php

namespace ManaPHP\Event;

use ManaPHP\Component;
use ManaPHP\Logging\Logger\LogCategorizable;

class Listener extends Component implements LogCategorizable
{
    /**
     * @var array
     */
    protected $processors = [];

    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', static::class), 'Listener');
    }

    public function __construct()
    {
        $methods = get_class_methods($this);
        sort($methods);

        foreach ($methods as $method) {
            if (!str_starts_with($method, 'on')) {
                continue;
            }

            $event = lcfirst(substr($method, 2));
            $event = rtrim($event, '0123456789');

            $this->processors[$event][] = $method;
        }
    }

    public function process(EventArgs $eventArgs)
    {
        list(, $type) = explode(':', $eventArgs->event, 2);
        if (method_exists($this, 'peek')) {
            if (($r = $this->peek($eventArgs)) !== null) {
                return $r;
            }
        }

        if (isset($this->processors[$type])) {
            foreach ($this->processors[$type] as $processor) {
                if (($r = $this->$processor($eventArgs)) !== null) {
                    return $r;
                }
            }
        }

        return null;
    }
}