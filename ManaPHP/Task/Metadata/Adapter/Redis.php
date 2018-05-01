<?php

namespace ManaPHP\Task\Metadata\Adapter;

use ManaPHP\Component;
use ManaPHP\Task\Metadata\AdapterInterface;

/**
 * Class ManaPHP\Task\Metadata\Adapter\Redis
 *
 * @package tasksMetadata\adapter
 *
 * @property \Redis $taskMetadataRedis
 */
class Redis extends Component implements AdapterInterface
{
    /**
     * @var string|\Redis
     */
    protected $_redis = 'redis';

    /**
     * @var string
     */
    protected $_prefix = 'tasks_metadata:';

    /**
     * Redis constructor.
     *
     * @param string|\Redis|array $options
     */
    public function __construct($options = 'redis')
    {
        if (is_string($options) || is_object($options)) {
            $this->_redis = $options;
        } else {
            if (isset($options['redis'])) {
                $this->_redis = $options['redis'];
            }

            if (isset($options['prefix'])) {
                $this->_prefix = $options['prefix'];
            }
        }
    }

    /**
     * @return \Redis
     */
    protected function _getRedis()
    {
        if (strpos($this->_redis, '/') !== false) {
            return $this->_redis = $this->_di->getInstance('ManaPHP\Redis', [$this->_redis]);
        } else {
            return $this->_redis = $this->_di->getShared($this->_redis);
        }
    }

    /**
     * @param string $key
     *
     * @return mixed|false
     */
    public function get($key)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        return $redis->get($this->_prefix . $key);
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function set($key, $value)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        $redis->set($this->_prefix . $key, $value);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function delete($key)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        $redis->delete($this->_prefix . $key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        return $redis->exists($this->_prefix . $key);
    }
}