<?php
namespace ManaPHP\Task;

use ManaPHP\Component;
use ManaPHP\Task;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Task\Metadata
 *
 * @package tasksMetadata
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
    protected $_prefix = '';

    /**
     * @var array
     */
    protected $_taskIds = [];

    /**
     * @var \ManaPHP\Task\Metadata\AdapterInterface
     */
    public $adapter;

    /**
     * Metadata constructor.
     *
     * @param string|array|\ManaPHP\Task\Metadata\AdapterInterface $options
     */
    public function __construct($options = [])
    {
        if (is_string($options) || is_object($options)) {
            $options = ['adapter' => $options];
        }

        $this->adapter = $options['adapter'];

        if (isset($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }
    }

    /**
     * @param \ManaPHP\DiInterface $dependencyInjector
     *
     * @return static
     */
    public function setDependencyInjector($dependencyInjector)
    {
        parent::setDependencyInjector($dependencyInjector);

        if (!is_object($this->adapter)) {
            $this->adapter = $this->_dependencyInjector->getShared($this->adapter);
        }

        return $this;
    }

    /**
     * @param string $task
     * @param string $field
     *
     * @return string
     */
    protected function _formatKey($task, $field)
    {
        if (!isset($this->_taskIds[$task])) {
            if (preg_match('#^[^/]*/([^/]*)/Tasks/([a-z\d]*)Task$#i', str_replace('\\', '/', $task), $match) === 1) {
                $this->_taskIds[$task] = $match[1] . ':' . $match[2];
            } else {
                $this->_taskIds[$task] = $task;
            }
        }

        return $this->_prefix . $this->_taskIds[$task] . ':' . $field;
    }

    /**
     * @param string $task
     * @param string $field
     *
     * @return mixed
     */
    public function get($task, $field)
    {
        return $this->adapter->get($this->_formatKey($task, $field));
    }

    /**
     * @param string $task
     *
     * @return array
     */
    public function getAll($task)
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $rc = new \ReflectionClass($this);

        $data = [];

        foreach ($rc->getConstants() as $n => $field) {
            if (!Text::startsWith($n, 'FIELD_')) {
                continue;
            }

            $value = $this->adapter->get($this->_formatKey($task, $field));
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
            $data['up_time_human'] = round($data['up_time'] / 86400) . ' days ' . gmstrftime('%H:%M:%S', $data['up_time'] % 86400);
        }

        if (isset($data[self::FIELD_KEEP_ALIVE_TIME])) {
            if (isset($data[self::FIELD_STOP_TIME])) {
                $keep_alive = strtotime($data[self::FIELD_STOP_TIME]) - strtotime($data[self::FIELD_KEEP_ALIVE_TIME]);
            } else {
                $keep_alive = time() - strtotime($data[self::FIELD_KEEP_ALIVE_TIME]);
            }

            /** @noinspection SummerTimeUnsafeTimeManipulationInspection */
            $data['keep_alive_time_human'] = round($keep_alive / 86400) . ' days ' . gmstrftime('%H:%M:%S', $keep_alive % 86400);
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
     * @param string $task
     * @param string $field
     * @param mixed  $value
     *
     * @return void
     */
    public function set($task, $field, $value)
    {
        $this->adapter->set($this->_formatKey($task, $field), $value);
    }

    /**
     * @param string $task
     * @param string $field
     *
     * @return void
     */
    public function delete($task, $field)
    {
        $this->adapter->delete($this->_formatKey($task, $field));
    }

    /**
     * @param string $task
     * @param string $field
     *
     * @return bool
     */
    public function exists($task, $field)
    {
        return $this->adapter->exists($this->_formatKey($task, $field));
    }

    /**
     * @param string $task
     *
     * @return void
     */
    public function reset($task)
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $rc = new \ReflectionClass($this);

        foreach ($rc->getConstants() as $n => $field) {
            if (!Text::startsWith($n, 'FIELD_')) {
                continue;
            }

            $this->adapter->delete($this->_formatKey($task, $field));
        }
    }
}