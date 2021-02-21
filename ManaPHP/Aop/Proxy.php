<?php

namespace ManaPHP\Aop;

class Proxy implements ProxyInterface
{
    /**
     * @var mixed
     */
    protected $targetObject;

    /**
     * @param mixed $targetObject
     */
    public function __construct($targetObject)
    {
        $this->targetObject = $targetObject;
    }

    /**
     * @return mixed
     */
    public function getTargetObject()
    {
        return $this->targetObject;
    }

    public function __call($name, $arguments)
    {
        $target = $this->targetObject;

        return $target->$name(...$arguments);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->targetObject->$name;
    }

    /**
     * @param mixed $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->targetObject->$name = $value;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->targetObject->$name);
    }
}