<?php

namespace ManaPHP\Caching\Cache\Adapter;

use ManaPHP\Caching\Cache;

class Redis extends Cache
{
    /**
     * @var string
     */
    protected $_prefix;

    /**
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
        return $this->redisCache->get($this->_prefix . $key);
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
        $this->redisCache->set($this->_prefix . $key, $value, $ttl);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function do_delete($key)
    {
        $this->redisCache->del($this->_prefix . $key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function do_exists($key)
    {
        return (bool)$this->redisCache->exists($this->_prefix . $key);
    }
}
