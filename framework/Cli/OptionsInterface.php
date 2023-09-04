<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

interface OptionsInterface
{
    public function parse(?array $arguments = null): static;

    public function get(null|string|int $name = null, mixed $default = null): mixed;

    public function has(string $name): bool;

    public function getValue(int $position, mixed $default = null): mixed;

    public function getValues(): array;

    public function completeShortNames(object $instance, string $action): void;
}