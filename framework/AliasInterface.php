<?php
declare(strict_types=1);

namespace ManaPHP;

interface AliasInterface
{
    public function all(): array;

    public function set(string $name, string $path): string;

    public function get(string $name): ?string;

    public function has(string $name): bool;

    public function resolve(string $path): string;
}