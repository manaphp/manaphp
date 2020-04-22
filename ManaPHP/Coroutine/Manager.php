<?php

namespace ManaPHP\Coroutine;

use ManaPHP\Component;
use Swoole\Coroutine;

class Manager extends Component implements ManagerInterface
{
    /**
     * @var array
     */
    protected $_option;

    /**
     * Manager constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->_option = $options;

        if (MANAPHP_COROUTINE_ENABLED) {
            Coroutine::set($options);
        }
    }

    /**
     * @return \ManaPHP\Coroutine\Scheduler|\ManaPHP\Coroutine\SchedulerInterface
     */
    public function createScheduler()
    {
        return $this->_di->get('ManaPHP\Coroutine\Scheduler');
    }

    /**
     * @param callable $fn
     * @param int      $count
     *
     * @return \ManaPHP\Coroutine\TaskInterface
     */
    public function createTask($fn, $count = 1)
    {
        return $this->_di->get('ManaPHP\Coroutine\Task', [$fn, $count]);
    }
}