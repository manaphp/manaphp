<?php

namespace ManaPHP\Cache\Engine;

use ManaPHP\Cache\EngineInterface;
use ManaPHP\Component;

/**
 * Class ManaPHP\Cache\Adapter\Redis
 *
 * @package cache\adapter
 */
class Redis extends Component implements EngineInterface
{
    /**
     * @var string
     */
    protected $_prefix = 'cache:';

    /**
     * @var string|\ManaPHP\Redis
     */
    protected $_redis = 'redis';

    /**
     * Redis constructor.
     *
     * @param string|array $options
     *
     * @throws \ManaPHP\Redis\Exception
     */
    public function __construct($options = 'redis')
    {
        if (is_string($options)) {
            $this->_redis = $options;
        } elseif (is_object($options)) {
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
     * @return \ManaPHP\Redis
     */
    protected function _getRedis()
    {
        if (strpos($this->_redis, '/') !== false) {
            return $this->_redis = $this->_dependencyInjector->getInstance('ManaPHP\Redis', [$this->_redis]);
        } else {
            return $this->_redis = $this->_dependencyInjector->getShared($this->_redis);
        }
    }

    /**
     * @param string $key
     *
     * @return string|false
     */
    public function get($key)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        return $redis->get($this->_prefix . $key);
    }

    /**
     * @param string $key
     * @param string $value
     * @param int    $ttl
     *
     * @return void
     */
    public function set($key, $value, $ttl)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        $redis->set($this->_prefix . $key, $value, $ttl);
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