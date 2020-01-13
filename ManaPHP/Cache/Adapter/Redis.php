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
    protected $_prefix;

    /**
     * Redis constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->_prefix = $options['prefix'] ?? "cache:{$this->configure->id}:";
    }

    /**
     * @param string $key
     *
     * @return string|false
     */
    public function do_get($key)
    {
        return $this->redis->get($this->_prefix . $key);
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
        $this->redis->set($this->_prefix . $key, $value, $ttl);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function do_delete($key)
    {
        $this->redis->del($this->_prefix . $key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function do_exists($key)
    {
        return (bool)$this->redis->exists($this->_prefix . $key);
    }
}
