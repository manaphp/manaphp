<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

interface OptionsInterface
{
    public function parse(array $argv): array;

    public function all(): array;

    public function get(string $name, mixed $default = null): ?string;

    public function has(string $name): bool;
}