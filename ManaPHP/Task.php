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
        return basename(str_replace('\\', '.', static::class), 'Task');
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
        $this->tasksManager->heartbeat(basename(static::class, 'Task'));
    }
}