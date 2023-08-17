<?php
declare(strict_types=1);

namespace ManaPHP\Pool;

interface PoolManagerInterface
{
    public function remove(object $owner, ?string $type = null): static;

    public function create(object $owner, int $capacity, string $type = 'default'): static;

    public function add(object $owner, object $sample, int $size = 1, string $type = 'default'): static;

    public function push(object $owner, object $instance, string $type = 'default'): static;

    public function pop(object $owner, ?float $timeout = null, string $type = 'default'): mixed;

    public function get(object $owner, ?float $timeout = null, string $type = 'default'): Proxy;

    public function transient(Transientable $owner, ?float $timeout = null, string $type = 'default'): Transient;

    public function exists(object $owner, string $type = 'default'): bool;

    public function size(object $owner, string $type = 'default'): int;
}