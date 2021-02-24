<?php /** @noinspection MagicMethodsValidityInspection */

namespace ManaPHP\Aop;

class Proxy implements ProxyInterface
{
    /**
     * @var mixed
     */
    protected $__target;

    /**
     * @param mixed $target
     */
    public function __construct($target)
    {
        $this->__target = $target;
    }

    /**
     * @return mixed
     */
    public function __getTarget()
    {
        return $this->__target;
    }

    public function __call($name, $arguments)
    {
        $target = $this->__target;

        return $target->$name(...$arguments);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->__target->$name;
    }

    /**
     * @param mixed $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->__target->$name = $value;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->__target->$name);
    }
}