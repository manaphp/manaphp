<?php /** @noinspection MagicMethodsValidityInspection */

namespace ManaPHP\Aop;

class Proxy implements ProxyInterface
{
    /**
     * @var mixed
     */
    protected $__target;

    /**
     * @var \ManaPHP\Aop\JoinPoint[]
     */
    protected $__joinPoints;

    /**
     * @param mixed                    $target
     * @param \ManaPHP\Aop\JoinPoint[] $joinPoints
     */
    public function __construct($target, $joinPoints)
    {
        $this->__target = $target;
        $this->__joinPoints = $joinPoints;
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
        if (($joinPoint = $this->__joinPoints[$name] ?? null) === null) {
            return $target->$name(...$arguments);
        } else {
            $joinPoint = clone $joinPoint;
            $joinPoint->args = $arguments;

            return $joinPoint->invoke();
        }
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