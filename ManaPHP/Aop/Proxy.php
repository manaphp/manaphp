<?php /** @noinspection MagicMethodsValidityInspection */

namespace ManaPHP\Aop;

class Proxy implements ProxyInterface
{
    /**
     * @var mixed
     */
    protected $__target;

    /**
     * @var \ManaPHP\Aop\JoinPointInterface[]
     */
    protected $__joinPoints;

    /**
     * @param mixed                             $target
     * @param \ManaPHP\Aop\JoinPointInterface[] $joinPoints
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

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (($joinPoint = $this->__joinPoints[$name] ?? null) === null) {
            $target = $this->__target;
            if ($target instanceof Proxyable) {
                return $target->__proxyCall($name, $arguments);
            } else {
                return $target->$name(...$arguments);
            }
        } else {
            $joinPoint = clone $joinPoint;
            return $joinPoint->invoke($arguments);
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
     * @param string $name
     * @param mixed  $value
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