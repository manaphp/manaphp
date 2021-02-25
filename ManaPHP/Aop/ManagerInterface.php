<?php

namespace ManaPHP\Aop;

interface ManagerInterface
{
    /**
     * @param string|array $class
     * @param string|array $method
     *
     * @return \ManaPHP\Aop\Advice
     */
    public function pointcut($class, $method);
}