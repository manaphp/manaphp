<?php

namespace ManaPHP;

use Closure;

interface AopInterface
{
    /**
     * @param string  $class
     * @param string  $method
     * @param Closure $signature
     *
     * @return \ManaPHP\Aop\Advice
     */
    public function pointcutMethod($class, $method, $signature = null);

    /**
     * @param string|array $classes
     * @param Closure      $closure
     *
     * @return void
     */
    public function pointCutMethods($classes = '*', $closure = null);

    /**
     * @param Closure $closure
     */
    public function test($closure = null);
}