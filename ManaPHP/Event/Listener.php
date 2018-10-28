<?php
namespace ManaPHP\Event;

use ManaPHP\Component;
use ManaPHP\Logger\LogCategorizable;

class Listener extends Component implements LogCategorizable
{
    /**
     * @var array
     */
    protected $_processors = [];

    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', get_called_class()), 'Listener');
    }

    public function __construct()
    {
        $methods = get_class_methods($this);
        sort($methods);

        foreach ($methods as $method) {
            if (strpos($method, 'on') !== 0) {
                continue;
            }

            $event = lcfirst(substr($method, 2));
            $event = rtrim($event, '0123456789');

            $this->_processors[$event][] = $method;
        }
    }

    public function process($event, $source, $data)
    {
        if (method_exists($this, 'peek')) {
            if (($r = $this->peek($event, $source, $data)) !== null) {
                return $r;
            }
        }

        if (isset($this->_processors[$event])) {
            foreach ($this->_processors[$event] as $processor) {
                if (($r = $this->$processor($source, $data)) !== null) {
                    return $r;
                }
            }
        }

        return null;
    }
}