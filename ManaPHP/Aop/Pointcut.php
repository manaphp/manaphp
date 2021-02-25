<?php

namespace ManaPHP\Aop;

class Pointcut
{
    /**
     * @var string|array
     */
    public $class;

    /**
     * @var string|array
     */
    public $method;

    /**
     * @var \ManaPHP\Aop\Advice
     */
    public $advice;

    public function __construct($class, $method, $advice)
    {
        $this->class = $class;
        $this->method = $method;
        $this->advice = $advice;
    }
}