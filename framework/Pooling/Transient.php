<?php
declare(strict_types=1);

namespace ManaPHP\Pooling;

class Transient
{
    protected PoolsInterface $pools;
    protected Transientable $owner;
    protected object $instance;
    protected string $type;

    public function __construct(PoolsInterface $pools, Transientable $owner, object $instance, string $type)
    {
        $this->pools = $pools;
        $this->owner = $owner;
        $this->instance = $instance;
        $this->type = $type;
    }

    public function __destruct()
    {
        $this->pools->push($this->owner, $this->instance, $this->type);
    }

    public function __call(string $method, array $arguments): mixed
    {
        return $this->owner->transientCall($this->instance, $method, $arguments);
    }
}