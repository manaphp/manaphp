<?php

namespace ManaPHP\Coroutine;

interface SchedulerInterface
{
    /**
     * @param callable $fn
     * @param mixed    ...$args
     *
     * @return static
     */
    public function add($fn, ...$args);

    /**
     * @return array
     */
    public function start();
}
