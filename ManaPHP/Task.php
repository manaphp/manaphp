<?php
namespace ManaPHP;

use ManaPHP\Logger\LogCategorizable;
use ManaPHP\Utility\Text;

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
        return Text::underscore(basename(get_called_class(), 'Task'));
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