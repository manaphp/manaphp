<?php
declare(strict_types=1);

namespace ManaPHP\Pool;

class Proxy
{
    protected ManagerInterface $manager;
    protected object $owner;
    protected object $instance;
    protected string $type;

    public function __construct(ManagerInterface $manager, object $owner, object $instance, string $type)
    {
        $this->manager = $manager;
        $this->owner = $owner;
        $this->instance = $instance;
        $this->type = $type;
    }

    /** @noinspection PhpMissingReturnTypeInspection */
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