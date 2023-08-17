<?php
declare(strict_types=1);

namespace ManaPHP\Coroutine;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Di\MakerInterface;
use Swoole\Coroutine;

class Manager implements ManagerInterface
{
    #[Inject] protected MakerInterface $maker;

    #[Value] protected array $option = [];

    public function __construct()
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            Coroutine::set($this->option);
        }
    }

    public function createScheduler(): SchedulerInterface
    {
        return $this->maker->make(SchedulerInterface::class);
    }

    public function createTask(callable $fn, int $count = 1): TaskInterface
    {
        return $this->maker->make(TaskInterface::class, [$fn, $count]);
    }
}