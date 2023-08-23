<?php
declare(strict_types=1);

namespace ManaPHP\Pooling;

class Proxy
{
    public function __construct(
        protected PoolManagerInterface $manager,
        protected object $owner,
        protected object $instance,
        protected string $type = 'default'
    ) {
    }

    public function __destruct()
    {
        $this->manager->push($this->owner, $this->instance, $this->type);
    }

    public function __call(string $method, array $arguments): mixed
    {
        /** @noinspection OneTimeUseVariablesInspection */
        $instance = $this->instance;
        return $instance->$method(...$arguments);
    }
}