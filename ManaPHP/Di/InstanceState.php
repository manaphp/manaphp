<?php
namespace ManaPHP\Di;

class InstanceState
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var \ManaPHP\Component
     */
    public $instance;

    /**
     * @var array
     */
    public $state;

    public function __construct($name, $instance, $state)
    {
        $this->name = $name;
        $this->instance = $instance;
        $this->state = $state;
    }
}