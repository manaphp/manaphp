<?php
declare(strict_types=1);

namespace ManaPHP;

interface SettingsInterface
{
    public function get(string $key, ?string $default = null): ?string;

    public function mGet(array $keys): array;

    public function set(string $key, string $value): static;

    public function mSet(array $kvs): static;

    public function exists(string $key): bool;

    public function delete(string $key): static;
}