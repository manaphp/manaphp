<?php
namespace ManaPHP;

use Closure;
use ManaPHP\Aop\JoinPoint;
use ManaPHP\Exception\RuntimeException;

class Aop implements AopInterface
{
    /**
     * @var JoinPoint[][]
     */
    protected $_method_joinPoints;

    /**
     * @param string $class
     * @param string $method
     * @param object $object
     *
     * @return mixed
     */
    public function invokeAspect($class, $method, $object)
    {
        $joinPoint = $this->_method_joinPoints[$class][$method];
        return $joinPoint->invokeAspect($object, func_get_args());
    }

    /**
     * @param string  $class
     * @param string  $method
     * @param Closure $closure
     *
     * @return \ManaPHP\Aop\JoinPoint
     */
    public function addPointCut($class, $method, $closure = null)
    {
        if (!method_exists($class, $method)) {
            throw new RuntimeException(['`:method` is not exists', 'method' => $method]);
        }

        if (!$joinPoint = $this->_method_joinPoints[$class][$method] ?? null) {
            runkit7_method_rename($class, $method, '#' . $method);
            if (is_string($closure)) {
                runkit7_method_add($class, $method, $closure, "return \$this->aop->invokeAspect($class,$method,\$this);");
            } else {
                $joinPoint = $this->_method_joinPoints[$class][$method] = new JoinPoint($class, $method);
                if ($closure === null) {
                    $closure = function () use ($joinPoint) {
                        return $joinPoint->invokeAspect($this, func_get_args());
                    };
                }

                runkit7_method_add($class, $method, $closure);
            }
        }

        return $joinPoint;
    }

    /**
     * @param string|array $class
     * @param string|array $methods
     * @param Closure      $closure
     *
     * @return static
     */
    public function addPointCuts($class, $methods, $closure)
    {
        if (is_array($class)) {
            foreach ($class as $_) {
                class_exists($_);
            }
            foreach ($class as $_) {
                $this->addPointCuts($_, $methods, $closure);
            }
        } elseif (!$methods) {
            null;
        } elseif (is_array($methods)) {
            foreach ($methods as $method) {
                $this->addPointCuts($class, $method, $closure);
            }
        } elseif ($methods === '*') {
            foreach ((array)get_class_methods($class) as $method) {
                if (strpos($method, '_') === 0) {
                    continue;
                }
                if (method_exists(get_parent_class($class), $method)) {
                    continue;
                }
                $closure($this->addPointCut($class, $method));
            }
        } elseif (strpos($methods, ',')) {
            foreach (preg_split('#[\s,]#', $methods, -1, PREG_SPLIT_NO_EMPTY) as $method) {
                $closure($this->addPointCut($class, $method));
            }
        } elseif (preg_match('#^\w+$#', $methods)) {
            $closure($this->addPointCut($class, $methods));
        } else {
            foreach (get_class_methods($class) as $method) {
                if (preg_match($methods, $method)) {
                    $closure($this->addPointCut($class, $method));
                }
            }
        }

        return $this;
    }
}