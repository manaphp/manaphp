<?php
declare(strict_types=1);

namespace ManaPHP;

interface ConfigInterface
{
    public function load(string $file = '@config/app.php'): array;

    public function get(?string $key = null, mixed $default = null): mixed;

    public function set(string $key, mixed $value): static;

    public function has(string $key): bool;
}