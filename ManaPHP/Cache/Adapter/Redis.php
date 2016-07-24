<?php

namespace ManaPHP\Cache\Adapter;

use ManaPHP\Cache;

class Redis extends Cache
{
    protected $_prefix = 'manaphp:cache:';

    /**
     * Redis constructor.
     *
     * @param string|array|\ConfManaPHP\Cache\Adapter\Redis $options
     */
    public function __construct($options = [])
    {
        if (is_object($options)) {
            $options = (array)$options;
        }

        if (is_string($options)) {
            $options['prefix'] = $options;
        }

        if (isset($options['prefix'])) {
            $this->_prefix .= $options['prefix'];
        }
    }

    /**
     * @param string $key
     *
     * @return string|false
     */
    public function _get($key)
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
    public function _set($key, $value, $ttl)
    {
        $this->redis->set($this->_prefix . $key, $value, $ttl);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function _delete($key)
    {
        $this->redis->delete($this->_prefix . $key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function _exists($key)
    {
        return $this->redis->exists($this->_prefix . $key);
    }
}