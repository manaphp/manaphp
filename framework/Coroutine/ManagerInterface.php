<?php
declare(strict_types=1);

namespace ManaPHP\Coroutine;

interface ManagerInterface
{
    public function createScheduler(): SchedulerInterface;

    public function createTask(callable $fn, int $count = 1): TaskInterface;
}