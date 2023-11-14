<?php
declare(strict_types=1);

namespace ManaPHP\Pooling;

class Pool
{
    protected PoolsInterface $pools;

    public function __construct(PoolsInterface $pools, int $capacity)
    {
        $this->pools = $pools;
        $pools->create($this, $capacity);
    }

    public function add(object|array $sample, int $size = 1): void
    {
        $this->pools->add($this, $sample, $size);
    }

    public function push(object $instance): void
    {
        $this->pools->push($this, $instance);
    }

    public function pop(?float $timeout = null): object
    {
        return $this->pools->pop($this, $timeout);
    }

    public function size(): int
    {
        return $this->pools->size($this);
    }
}