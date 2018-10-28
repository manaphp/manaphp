<?php
namespace ManaPHP\Event;

use ManaPHP\Component;

class Listener extends Component
{
    /**
     * @var array
     */
    protected $_processors = [];

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