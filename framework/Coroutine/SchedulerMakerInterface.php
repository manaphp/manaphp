<?php
declare(strict_types=1);

namespace ManaPHP\Coroutine;

interface SchedulerMakerInterface
{
    public function make(array $parameters = []): mixed;
}