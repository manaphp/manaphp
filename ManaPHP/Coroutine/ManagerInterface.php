<?php

namespace ManaPHP\Coroutine;

interface ManagerInterface
{
    /**
     * @return \ManaPHP\Coroutine\SchedulerInterface
     */
    public function createScheduler();

    /**
     * @param callable $fn
     * @param int      $count
     *
     * @return \ManaPHP\Coroutine\TaskInterface
     */
    public function createTask($fn, $count = 1);
}