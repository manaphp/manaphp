<?php
namespace ManaPHP\Task\Metadata\Adapter;

use ManaPHP\Component;
use ManaPHP\Task\Metadata\AdapterInterface;

/**
 * Class ManaPHP\Task\Metadata\Adapter\Db
 *
 * @package tasksMetadata\adapter
 *
 * @property \Redis $redis
 */
class Db extends Component implements AdapterInterface
{
    /**
     * @var string
     */
    protected $_model = '\ManaPHP\Task\Metadata\Adapter\Db\Model';

    /**
     * Db constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = [])
    {
        if (is_object($options)) {
            $options = (array)$options;
        } elseif (is_string($options)) {
            $options = ['model' => $options];
        }

        if (isset($options['model'])) {
            $this->_model = $options['model'];
        }
    }

    /**
     * @param string $key
     *
     * @return mixed|false
     * @throws \ManaPHP\Model\Exception
     */
    public function get($key)
    {
        /**
         * @var \ManaPHP\Task\Metadata\Adapter\Db\Model $instance
         */
        $instance = new $this->_model;
        $instance = $instance::findFirst(['id' => md5($key)]);

        if ($instance === false) {
            return $instance;
        } else {
            return $instance->value;
        }
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     * @throws \ManaPHP\Model\Exception
     */
    public function set($key, $value)
    {
        /**
         * @var \ManaPHP\Task\Metadata\Adapter\Db\Model $instance
         */
        $instance = new $this->_model;

        $instance->id = md5($key);
        $instance->key = $key;
        $instance->value = $value;

        $instance->save();
    }

    /**
     * @param string $key
     *
     * @return void
     * @throws \ManaPHP\Model\Exception
     */
    public function delete($key)
    {
        /**
         * @var \ManaPHP\Task\Metadata\Adapter\Db\Model $instance
         */
        $instance = new $this->_model;
        $instance::deleteAll(['id' => md5($key)]);
    }

    /**
     * @param string $key
     *
     * @return bool
     * @throws \ManaPHP\Model\Exception
     */
    public function exists($key)
    {
        /**
         * @var \ManaPHP\Task\Metadata\Adapter\Db\Model $instance
         */
        $instance = new $this->_model;

        return $instance::exists(['id' => md5($key)]);
    }
}