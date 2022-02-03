<?php
declare(strict_types=1);

namespace ManaPHP\Coroutine;

interface TaskInterface
{
    public function push(mixed $data, int $timeout = -1): bool;

    public function close(): void;
}