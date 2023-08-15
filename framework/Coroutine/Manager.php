<?php
declare(strict_types=1);

namespace ManaPHP\Coroutine;

use ManaPHP\Component;
use ManaPHP\Di\Attribute\Inject;
use Swoole\Coroutine;

class Manager extends Component implements ManagerInterface
{
    #[Inject] protected SchedulerMakerInterface $schedulerMaker;
    #[Inject] protected TaskMakerInterface $taskMaker;

    protected array $option;

    public function __construct(array $options = [])
    {
        $this->option = $options;

        if (MANAPHP_COROUTINE_ENABLED) {
            Coroutine::set($options);
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