<?php
declare(strict_types=1);

namespace ManaPHP\Coroutine;

interface SchedulerInterface
{
    public function add(callable $fn, ...$args): static;

    public function start(): array;
}
