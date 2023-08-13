<?php
declare(strict_types=1);

namespace ManaPHP\Coroutine;

use ManaPHP\Component;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\FactoryInterface;
use Swoole\Coroutine;

class Manager extends Component implements ManagerInterface
{
    #[Inject]
    protected FactoryInterface $factory;

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
        return $this->factory->make('ManaPHP\Coroutine\Scheduler');
    }

    public function createTask(callable $fn, int $count = 1): TaskInterface
    {
        return $this->factory->make('ManaPHP\Coroutine\Task', [$fn, $count]);
    }
}