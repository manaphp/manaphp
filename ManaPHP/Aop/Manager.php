<?php

namespace ManaPHP\Aop;

use ManaPHP\Component;
use ReflectionClass;
use ReflectionMethod;

class Manager extends Component implements ManagerInterface
{
    /**
     * @var \ManaPHP\Aop\Pointcut[]
     */
    protected $pointcuts = [];

    public function __construct()
    {
        $this->container->on('resolved', [$this, 'onContainerResolved']);
    }

    /**
     * @param string|array $pattern
     * @param string       $str
     *
     * @return bool
     */
    protected function isMatch($patterns, $str)
    {
        if (is_string($patterns)) {
            if (str_contains($patterns, '*')) {
                if (fnmatch($patterns, $str)) {
                    return true;
                }
            } elseif ($patterns === $str) {
                return true;
            }
        } else {
            foreach ($patterns as $pattern) {
                if (str_contains($pattern, '*')) {
                    if (fnmatch($pattern, $str)) {
                        return true;
                    }
                } elseif ($pattern === $str) {
                    return true;
                }
            }
        }

        return false;
    }

    public function onContainerResolved($instance)
    {
        $class = get_class($instance);

        $pointcuts = [];
        foreach ($this->pointcuts as $pointcut) {
            if ($this->isMatch($pointcut->class, $class)) {
                $pointcuts[] = $pointcut;
            }
        }

        if ($pointcuts) {
            $joinPoints = [];
            $rc = new ReflectionClass($instance);
            foreach ($rc->getMethods(ReflectionMethod::IS_PUBLIC) as $rm) {
                $method = $rm->getName();
                if (str_contains($method, '_')) {
                    continue;
                }

                $advices = [];
                foreach ($pointcuts as $pointcut) {
                    if ($this->isMatch($pointcut->method, $method)) {
                        $advices[] = $pointcut->advice;
                    }
                }

                if ($advices) {
                    $joinPoints[$method] = new JoinPoint($instance, $method, $advices);
                }
            }

            return new Proxy($instance, $joinPoints);
        } else {
            return $instance;
        }
    }

    /**
     * @param string|array $class
     * @param string|array $method
     *
     * @return \ManaPHP\Aop\Advice
     */
    public function pointcut($class, $method)
    {
        $advice = new Advice();
        $this->pointcuts[] = new Pointcut($class, $method, $advice);

        return $advice;
    }
}