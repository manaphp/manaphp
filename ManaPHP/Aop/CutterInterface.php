<?php

namespace ManaPHP\Aop;

interface CutterInterface
{
    /**
     * @param string $class
     * @param string $method
     * @param string $signature
     *
     * @return \ManaPHP\Aop\Advice
     */
    public function pointcutMethod($class, $method, $signature = null);

    /**
     * @param string|array $classes
     * @param callable     $closure
     *
     * @return void
     */
    public function pointCutMethods($classes = '*', $closure = null);

    /**
     * @param callable $closure
     */
    public function test($closure = null);
}