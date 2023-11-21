<?php
declare(strict_types=1);

namespace ManaPHP\Pooling;

interface PoolsInterface
{
    public function remove(object $owner, ?string $type = null): void;

    public function create(object $owner, int $capacity, string $type = 'default'): void;

    public function add(object $owner, object|array $sample, int $size = 1, string $type = 'default'): void;

    public function push(object $owner, object $instance, string $type = 'default'): void;

    public function pop(object $owner, ?float $timeout = null, string $type = 'default'): object;

    public function get(object $owner, ?float $timeout = null, string $type = 'default'): Proxy;

    public function exists(object $owner, string $type = 'default'): bool;

    public function size(object $owner, string $type = 'default'): int;
}