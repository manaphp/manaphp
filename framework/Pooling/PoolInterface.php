<?php
declare(strict_types=1);

namespace ManaPHP\Pooling;

interface PoolInterface
{
    public function push(object $instance): void;

    public function pop(?float $timeout = null): object;

    public function size(): int;
}