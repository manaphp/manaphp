<?php
namespace ManaPHP;

class Coroutine extends Component implements CoroutineInterface
{
    /**
     * @return \ManaPHP\Coroutine\Scheduler|\ManaPHP\Coroutine\SchedulerInterface
     */
    public function createScheduler()
    {
        return $this->_di->getInstance('ManaPHP\Coroutine\Scheduler');
    }

    /**
     * @param callable $fn
     * @param int      $count
     *
     * @return \ManaPHP\Coroutine\TaskInterface
     */
    public function createTask($fn, $count = 1)
    {
        return $this->_di->getInstance('ManaPHP\Coroutine\Task', [$fn, $count]);
    }
}