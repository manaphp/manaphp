<?php
declare(strict_types=1);

namespace ManaPHP\Swoole;

use ManaPHP\Di\Attribute\Autowired;

abstract class AbstractProcess implements ProcessInterface
{
    #[Autowired] protected int $number_of_instances = 1;
    #[Autowired] protected bool $enabled = true;
    #[Autowired] protected bool $enable_coroutine = true;
    #[Autowired] protected int $pipe_type = SOCK_DGRAM;

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getNumberOfInstances(): int
    {
        return $this->number_of_instances;
    }

    public function isEnableCoroutine(): bool
    {
        return $this->enable_coroutine;
    }

    public function getPipeType(): int
    {
        return $this->pipe_type;
    }
}