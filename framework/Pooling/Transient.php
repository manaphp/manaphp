<?php
declare(strict_types=1);

namespace ManaPHP\Pooling;

class Transient
{
    protected PoolManagerInterface $manager;
    protected Transientable $owner;
    protected object $instance;
    protected string $type;

    public function __construct(PoolManagerInterface $manager, Transientable $owner, object $instance, string $type)
    {
        $this->manager = $manager;
        $this->owner = $owner;
        $this->instance = $instance;
        $this->type = $type;
    }

    public function __destruct()
    {
        $this->manager->push($this->owner, $this->instance, $this->type);
    }

    public function __call(string $method, array $arguments): mixed
    {
        return $this->owner->transientCall($this->instance, $method, $arguments);
    }
}