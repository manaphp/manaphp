<?php

namespace ManaPHP\Cache\Adapter;

use ManaPHP\Cache;

/**
 * Class ManaPHP\Cache\Adapter\Redis
 *
 * @package cache\adapter
 */
class Redis extends Cache
{
    /**
     * @var string
     */
    protected $_prefix = 'cache:';

    /**
     * @var string|\Redis
     */
    protected $_redis = 'redis';

    /**
     * Redis constructor.
     *
     * @param array $options
     *
     */
    public function __construct($options = [])
    {
        if (isset($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }

        if (isset($options['redis'])) {
            $this->_redis = $options['redis'];
        }
    }

    /**
     * @return \Redis
     */
    protected function _getRedis()
    {
        if (strpos($this->_redis, '/') !== false) {
            return $this->_redis = $this->_di->get('ManaPHP\Redis', [$this->_redis]);
        } else {
            return $this->_redis = $this->_di->getShared($this->_redis);
        }
    }

    /**
     * @param string $key
     *
     * @return string|false
     */
    public function do_get($key)
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
    public function do_set($key, $value, $ttl)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        $redis->set($this->_prefix . $key, $value, $ttl);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function do_delete($key)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        $redis->delete($this->_prefix . $key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function do_exists($key)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        return (bool)$redis->exists($this->_prefix . $key);
    }
}