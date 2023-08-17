<?php
declare(strict_types=1);

namespace ManaPHP\Eventing;

interface EmitterInterface
{
    public function on(string $event, callable $handler): void;

    public function emit(string $event, mixed $data = null): mixed;
}