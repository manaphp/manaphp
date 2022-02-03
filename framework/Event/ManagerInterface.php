<?php
declare(strict_types=1);

namespace ManaPHP\Event;

interface ManagerInterface
{
    public function attachEvent(string $event, callable $handler, int $priority = 0): void;

    public function detachEvent(string $event, callable $handler): void;

    public function fireEvent(string $event, mixed $data = null, ?object $source = null): EventArgs;

    public function peekEvent(string $group, callable $handler): void;
}