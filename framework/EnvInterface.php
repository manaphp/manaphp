<?php
declare(strict_types=1);

namespace ManaPHP;

interface EnvInterface
{
    public function load(): static;

    public function all(): array;

    public function get(string $key, mixed $default = null): mixed;
}