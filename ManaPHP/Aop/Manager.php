<?php

namespace ManaPHP\Aop;

use ManaPHP\Component;
use ReflectionClass;
use ReflectionMethod;

class Manager extends Component implements ManagerInterface
{
    /**
     * @var \ManaPHP\Aop\Advisor[][]
     */
    protected $advisors = [];

    public function __construct()
    {
        $this->container->on('resolved', [$this, 'onContainerResolved']);
    }

    public function onContainerResolved($instance)
    {
        $class = get_class($instance);

        $advisorsOfClass = [];
        foreach ($this->advisors as $type => $_advisors) {
            foreach ($_advisors as $advisor) {
                if ($advisor->isClassMatch($class)) {
                    $advisorsOfClass[$type][] = $advisor;
                }
            }
        }

        if ($advisorsOfClass) {
            $joinPoints = [];
            $rc = new ReflectionClass($instance);
            foreach ($rc->getMethods(ReflectionMethod::IS_PUBLIC) as $rm) {
                $method = $rm->getName();
                if (str_contains($method, '_')) {
                    continue;
                }

                $advisorsOfMethod = [];
                foreach ($advisorsOfClass as $type => $advisors) {
                    foreach ($advisors as $advisor) {
                        if ($advisor->isMethodMatch($method)) {
                            $advisorsOfMethod[$type][] = $advisor;
                        }
                    }
                }

                if ($advisorsOfMethod) {
                    foreach ($advisorsOfMethod as $type => $advisors) {
                        usort(
                            $advisors, static function ($a, $b) {
                            return $a->order - $b->order;
                        }
                        );
                    }
                    $joinPoints[$method] = new JoinPoint($instance, $method, $advisorsOfMethod);
                }
            }

            return new Proxy($instance, $joinPoints);
        } else {
            return $instance;
        }
    }

    /**
     * @param string|array $pointcut
     * @param callable     $advice
     * @param int          $order
     *
     * @return static
     */
    public function before($pointcut, $advice, $order = 0)
    {
        $this->advisors[Advisor::ADVICE_BEFORE][] = new Advisor($pointcut, $advice, $order);

        return $this;
    }

    /**
     * @param string|array $pointcut
     * @param callable     $advice
     * @param int          $order
     *
     * @return static
     */
    public function after($pointcut, $advice, $order = 0)
    {
        $this->advisors[Advisor::ADVICE_AFTER][] = new Advisor($pointcut, $advice, $order);

        return $this;
    }

    /**
     * @param string|array $pointcut
     * @param callable     $advice
     * @param int          $order
     *
     * @return static
     */
    public function afterReturning($pointcut, $advice, $order = 0)
    {
        $this->advisors[Advisor::ADVICE_AFTER_RETURNING][] = new Advisor($pointcut, $advice, $order);

        return $this;
    }

    /**
     * @param string|array $pointcut
     * @param callable     $advice
     * @param int          $order
     *
     * @return static
     */
    public function afterThrowing($pointcut, $advice, $order = 0)
    {
        $this->advisors[Advisor::ADVICE_AFTER_THROWING][] = new Advisor($pointcut, $advice, $order);

        return $this;
    }

    /**
     * @param string|array $pointcut
     * @param callable     $advice
     * @param int          $order
     *
     * @return static
     */
    public function around($pointcut, $advice, $order = 0)
    {
        $this->advisors[Advisor::ADVICE_AROUND][] = new Advisor($pointcut, $advice, $order);

        return $this;
    }
}