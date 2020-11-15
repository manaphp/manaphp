<?php

namespace ManaPHP\Caching\Cache\Adapter;

use ManaPHP\Caching\Cache;
use ManaPHP\Exception\RuntimeException;

/**
 * Class ManaPHP\Caching\Cache\Adapter\Apcu
 *
 * @package cache\adapter
 */
class Apcu extends Cache
{
    /**
     * @var string
     */
    protected $_prefix = 'cache:';

    /**
     * Cache constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function do_exists($key)
    {
        return apcu_exists($this->_prefix . $key);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function do_get($key)
    {
        return apcu_fetch($this->_prefix . $key);
    }

    /**
     * @param string $key
     * @param string $value
     * @param int    $ttl
     */
    public function do_set($key, $value, $ttl)
    {
        $r = apcu_store($this->_prefix . $key, $value, $ttl);
        if (!$r) {
            throw new RuntimeException(['apcu_store failed for `:key` key', 'key' => $key]);
        }
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function do_delete($key)
    {
        apcu_delete($this->_prefix . $key);
    }
}