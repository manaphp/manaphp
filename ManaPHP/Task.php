<?php
namespace ManaPHP;

use ManaPHP\Logger\LogCategorizable;
use ManaPHP\Task\Exception as TaskException;
use ManaPHP\Task\Metadata;
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
    const STATUS_NONE = 0;
    const STATUS_RUNNING = 1;
    const STATUS_STOP = 2;

    const STOP_TYPE_CANCEL = 1;
    const STOP_TYPE_EXCEPTION = 2;
    const STOP_TYPE_MEMORY_LIMIT = 3;

    /**
     * @var int
     */
    protected $_memoryLimit = 16;

    /**
     * @var string
     */
    protected $_taskId;

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
        if ($this->_taskId) {
            $this->tasksManager->heartbeat($this->_taskId);
        }
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->_taskId;
    }

    /**
     * @param string $id
     *
     * @return static
     */
    public function setId($id)
    {
        $this->_taskId = $id;

        return $this;
    }
}