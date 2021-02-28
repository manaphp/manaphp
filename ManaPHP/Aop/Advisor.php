<?php

namespace ManaPHP\Aop;

class Advisor
{
    const ADVICE_AROUND = 'around';
    const ADVICE_BEFORE = 'before';
    const ADVICE_AFTER = 'after';
    const ADVICE_AFTER_RETURNING = 'afterReturning';
    const ADVICE_AFTER_THROWING = 'afterThrowing';

    /**
     * @var string
     */
    public $pointcut;

    /**
     * @var callable
     */
    public $advice;

    /**
     * @var int
     */
    public $order;

    /**
     * @var string
     */
    protected $class;

    /**
     * @var string
     */
    protected $method;

    /**
     * Advisor constructor.
     *
     * @param string|array $pointcut
     * @param callable     $advice
     * @param int          $order
     */
    public function __construct($pointcut, $advice, $order)
    {
        $this->pointcut = $pointcut;
        $this->advice = $advice;
        $this->order = $order;

        $parts = is_string($pointcut) ? explode('::', $pointcut, 2) : $pointcut;
        if (count($parts) !== 2) {
            throw new PointcutExpressionException("`$pointcut` is not controller::action format");
        }

        list($this->class, $this->method) = $parts;
    }

    /**
     * @param string $class
     *
     * @return bool
     */
    public function isClassMatch($class)
    {
        $pattern = $this->class;

        if ($pattern === '*' || $pattern === $class) {
            return true;
        } else {
            return fnmatch($pattern, $class);
        }
    }

    /**
     * @param string $method
     *
     * @return bool
     */
    public function isMethodMatch($method)
    {
        $pattern = $this->method;

        if ($pattern === '*' || $pattern === $method) {
            return true;
        } else {
            return fnmatch($pattern, $method);
        }
    }

    /**
     * @param \ManaPHP\Aop\JoinPointInterface
     *
     * @return void
     */
    public function advise($joinPoint)
    {
        $advice = $this->advice;
        $advice($joinPoint);
    }
}