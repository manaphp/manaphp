<?php

namespace ManaPHP\Pool;

class Transient
{
    /**
     * @var \ManaPHP\Pool\ManagerInterface
     */
    protected $manager;

    /**
     * @var \ManaPHP\Pool\Transientable
     */
    protected $owner;

    /**
     * @var mixed
     */
    protected $instance;

    /**
     * @var string
     */
    protected $type;

    /**
     * @param \ManaPHP\Pool\ManagerInterface $manager
     * @param \ManaPHP\Pool\Transientable    $owner
     * @param mixed                          $instance
     * @param string                         $type
     */
    public function __construct($manager, $owner, $instance, $type)
    {
        $this->manager = $manager;
        $this->owner = $owner;
        $this->instance = $instance;
        $this->type = $type;
    }

    public function __destruct()
    {
        $this->manager->push($this->owner, $this->instance, $this->type);
    }

    /**
     * @param string $method
     * @param array  $arguments
     *
     * @return bool|mixed
     */
    public function __call($method, $arguments)
    {
        return $this->owner->transientCall($this->instance, $method, $arguments);
    }
}