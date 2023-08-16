<?php
declare(strict_types=1);

namespace ManaPHP\Coroutine;

use ManaPHP\Component;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use Swoole\Coroutine;

class Manager extends Component implements ManagerInterface
{
    #[Inject] protected SchedulerMakerInterface $schedulerMaker;
    #[Inject] protected TaskMakerInterface $taskMaker;

    #[Value] protected array $option = [];

    public function __construct()
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            Coroutine::set($this->option);
        }
    }

    public function createScheduler(): SchedulerInterface
    {
        return $this->schedulerMaker->make();
    }

    public function createTask(callable $fn, int $count = 1): TaskInterface
    {
        return $this->taskMaker->make([$fn, $count]);
    }
}