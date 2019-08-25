<?php
namespace ManaPHP;

use Closure;

interface AopInterface
{
    /**
     * @param string  $class
     * @param string  $method
     * @param Closure $closure
     *
     * @return \ManaPHP\Aop\JoinPoint
     */
    public function addPointCut($class, $method, $closure = null);

    /**
     * @param string|array $class
     * @param string|array $methods
     * @param Closure      $closure
     *
     * @return static
     */
    public function addPointCuts($class, $methods, $closure);
}