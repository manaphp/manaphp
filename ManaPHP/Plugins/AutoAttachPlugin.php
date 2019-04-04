<?php
namespace ManaPHP\Plugins;

use ManaPHP\Plugin;

abstract class AutoAttachPlugin extends Plugin
{
    /**
     * Plugin constructor.
     *
     * @param array $options
     */
    public function __construct($options = null)
    {
        if ($options) {
            foreach ($options as $k => $v) {
                $property = "_$k";
                $this->$property = $v;
            }
        }

        foreach (get_class_methods($this) as $method) {
            if (strpos($method, 'on') === 0) {
                $this->eventsManager->attachEvent('request:' . lcfirst(substr($method, 2)), [$this, $method]);
            }
        }
    }
}