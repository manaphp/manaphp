<?php
namespace ManaPHP;

use ManaPHP\Logger\LogCategorizable;

/**
 * Class ManaPHP\Task
 *
 * @package task
 *
 * @property \ManaPHP\Http\ResponseInterface $response
 */
abstract class Task extends Component implements TaskInterface, LogCategorizable
{
    public function categorizeLog()
    {
        $className = get_called_class();
        return 'task.' . basename(substr($className, strrpos($className, '\\') + 1), 'Task');
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