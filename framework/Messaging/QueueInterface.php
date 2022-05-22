<?php
declare(strict_types=1);

namespace ManaPHP\Messaging;

interface QueueInterface
{
    public const PRIORITY_HIGHEST = 1;
    public const PRIORITY_NORMAL = 5;
    public const PRIORITY_LOWEST = 9;

    public function push(string $topic, string $body, int $priority = self::PRIORITY_NORMAL): void;

    public function pop(string $topic, int $timeout = PHP_INT_MAX): ?string;

    public function delete(string $topic): void;

    public function length(string $topic, ?int $priority = null): int;
}