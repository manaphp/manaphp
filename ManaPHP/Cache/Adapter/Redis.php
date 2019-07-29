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
     * @param string $key
     *
     * @return string|false
     */
    public function do_get($key)
    {
        if (is_string($this->_redis)) {
            $this->_redis = $this->_di->getShared($this->_redis);
        }

        return $this->_redis->get($this->_prefix . $key);
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
        if (is_string($this->_redis)) {
            $this->_redis = $this->_di->getShared($this->_redis);
        }

        $this->_redis->set($this->_prefix . $key, $value, $ttl);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function do_delete($key)
    {
        if (is_string($this->_redis)) {
            $this->_redis = $this->_di->getShared($this->_redis);
        }

        $this->_redis->delete($this->_prefix . $key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function do_exists($key)
    {
        if (is_string($this->_redis)) {
            $this->_redis = $this->_di->getShared($this->_redis);
        }

        return (bool)$this->_redis->exists($this->_prefix . $key);
    }
}