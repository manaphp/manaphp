<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

interface RequestInterface
{
    public function parse(?array $arguments = null): static;

    public function get(null|string|int $name = null, mixed $default = null): mixed;

    public function has(string $name): bool;

    public function getValue(int $position, mixed $default = null): mixed;

    public function getValues(): array;

    public function getServer(?string $name = null, mixed $default = ''): mixed;

    public function hasServer(string $name): bool;

    public function getRequestId(): string;

    public function setRequestId(?string $request_id = null): void;

    public function completeShortNames(object $instance, string $action):void;
}