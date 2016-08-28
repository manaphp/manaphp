<?php
namespace ManaPHP\Task;

use ManaPHP\Component;
use ManaPHP\Task;
use ManaPHP\Utility\Text;

/**
 * Class Metadata
 *
 * @package ManaPHP\Task
 *
 * @property \redis $redis
 */
class Metadata extends Component implements MetadataInterface
{
    const FIELD_CLASS = 'class';
    const FIELD_START_TIME = 'start_time';
    const FIELD_STATUS = 'status';
    const FIELD_KEEP_ALIVE_TIME = 'keep_alive_time';
    const FIELD_MEMORY_PEAK_USAGE = 'memory_peak_usage';
    const FIELD_STOP_TIME = 'stop_time';
    const FIELD_DURATION_TIME = 'duration_time';
    const FIELD_DURATION_TIME_HUMAN = 'duration_time_human';
    const FIELD_STOP_REASON = 'stop_reason';
    const FIELD_CANCEL_FLAG = 'cancel';
    const FIELD_STOP_TYPE = 'stop_type';

    /**
     * @var string
     */
    protected $_prefix = 'manaphp:task:';

    /**
     * @var array
     */
    protected $_taskIds = [];

    /**
     * @param string|\ManaPHP\TaskInterface $task
     * @param string                        $field
     *
     * @return string
     */
    protected function _formatKey($task, $field)
    {
        $taskName = is_string($task) ? $task : get_class($task);
        if (!isset($this->_taskIds[$taskName])) {
            if (preg_match('#^[^/]*/([^/]*)/Tasks/([a-z\d]*)Task$#i', str_replace('\\', '/', $taskName), $match) === 1) {
                $this->_taskIds[$taskName] = $match[1] . ':' . $match[2];
            } else {
                $this->_taskIds[$taskName] = $taskName;
            }
        }

        return $this->_prefix . $this->_taskIds[$taskName] . ':' . $field;
    }

    /**
     * @param string|\ManaPHP\TaskInterface $task
     * @param string                        $field
     *
     * @return mixed
     */
    public function get($task, $field)
    {
        return $this->redis->get($this->_formatKey($task, $field));
    }

    /**
     * @param string|\ManaPHP\TaskInterface $task
     *
     * @return array
     */
    public function getAll($task)
    {
        $rc = new \ReflectionClass($this);

        $data = [];

        foreach ($rc->getConstants() as $n => $field) {
            if (!Text::startsWith($n, 'FIELD_')) {
                continue;
            }

            $value = $this->redis->get($this->_formatKey($task, $field));
            if ($value !== false) {
                $data[(string)$field] = $value;
            }
        }

        if (isset($data[self::FIELD_START_TIME])) {
            if (isset($data[self::FIELD_STOP_TIME])) {
                $data['up_time'] = strtotime($data[self::FIELD_STOP_TIME]) - strtotime($data[self::FIELD_START_TIME]);
            } else {
                $data['up_time'] = time() - strtotime($data[self::FIELD_START_TIME]);
            }

            /** @noinspection SummerTimeUnsafeTimeManipulationInspection */
            $data['up_time_human'] = round($data['up_time'] / 3600 / 24) . ' days ' . gmstrftime('%H:%M:%S', $data['up_time'] % (3600 * 24));
        }

        if (isset($data[self::FIELD_KEEP_ALIVE_TIME])) {
            if (isset($data[self::FIELD_STOP_TIME])) {
                $keep_alive = strtotime($data[self::FIELD_STOP_TIME]) - strtotime($data[self::FIELD_KEEP_ALIVE_TIME]);
            } else {
                $keep_alive = time() - strtotime($data[self::FIELD_KEEP_ALIVE_TIME]);
            }

            /** @noinspection SummerTimeUnsafeTimeManipulationInspection */
            $data['keep_alive_time_human'] = round($keep_alive / 3600 / 24) . ' days ' . gmstrftime('%H:%M:%S', $keep_alive % (3600 * 24));
        }

        if (!isset($data[self::FIELD_STATUS])) {
            $data[self::FIELD_STATUS] = Task::STATUS_NONE;
        }

        if (!isset($data[self::FIELD_CLASS])) {
            $data[self::FIELD_CLASS] = $task;
        }

        ksort($data);

        return $data;
    }

    /**
     * @param string|\ManaPHP\TaskInterface $task
     * @param string                        $field
     * @param mixed                         $value
     *
     * @return void
     */
    public function set($task, $field, $value)
    {
        $this->redis->set($this->_formatKey($task, $field), $value);
    }

    /**
     * @param string|\ManaPHP\TaskInterface $task
     * @param string                        $field
     *
     * @return void
     */
    public function delete($task, $field)
    {
        $this->redis->delete($this->_formatKey($task, $field));
    }

    /**
     * @param string|\ManaPHP\TaskInterface $task
     * @param string                        $field
     *
     * @return bool
     */
    public function exists($task, $field)
    {
        return $this->redis->exists($this->_formatKey($task, $field));
    }

    /**
     * @param string|\ManaPHP\TaskInterface $task
     *
     * @return void
     */
    public function reset($task)
    {
        $rc = new \ReflectionClass($this);

        foreach ($rc->getConstants() as $n => $field) {
            if (!Text::startsWith($n, 'FIELD_')) {
                continue;
            }

            $this->redis->delete($this->_formatKey($task, $field));
        }
    }
}