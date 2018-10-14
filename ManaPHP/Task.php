<?php
namespace ManaPHP;

use ManaPHP\Logger\LogCategorizable;

/**
 * Class ManaPHP\Task
 *
 * @package task
 */
abstract class Task extends Component implements TaskInterface, LogCategorizable
{
    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', get_called_class()), 'Task');
    }

    /**
     * @return int
     */
    public function getMaxDelay()
    {
        return 60;
    }

    /**
     * @return int
     */
    public function getInterval()
    {
        return 1;
    }

    public function heartbeat()
    {
        $this->tasksManager->heartbeat(basename(get_called_class(), 'Task'));
    }
}