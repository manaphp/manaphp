<?php

namespace ManaPHP\Caching\Cache\Adapter;

use ManaPHP\Caching\AbstractCache;
use ManaPHP\Exception\RuntimeException;

class Apcu extends AbstractCache
{
    /**
     * @var string
     */
    protected $prefix = 'cache:';

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['prefix'])) {
            $this->prefix = $options['prefix'];
        }
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function do_exists($key)
    {
        return apcu_exists($this->prefix . $key);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function do_get($key)
    {
        return apcu_fetch($this->prefix . $key);
    }

    /**
     * @param string $key
     * @param string $value
     * @param int    $ttl
     */
    public function do_set($key, $value, $ttl)
    {
        $r = apcu_store($this->prefix . $key, $value, $ttl);
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
        apcu_delete($this->prefix . $key);
    }
}