<?php
declare(strict_types=1);

namespace ManaPHP\Coroutine;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\MakerInterface;
use Swoole\Coroutine;

class CoroutineManager implements CoroutineManagerInterface
{
    #[Autowired] protected MakerInterface $maker;

    #[Autowired] protected array $option = [];

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