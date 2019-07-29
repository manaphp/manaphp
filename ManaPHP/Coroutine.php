<?php
namespace ManaPHP;

class Coroutine extends Component implements CoroutineInterface
{
    /**
     * @var array
     */
    protected $_option;

    /**
     * Coroutine constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->_option = $options;
        
        if (MANAPHP_COROUTINE_ENABLED) {
            \Swoole\Coroutine::set($options);
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