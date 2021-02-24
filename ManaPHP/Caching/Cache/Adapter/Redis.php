<?php

namespace ManaPHP\Caching\Cache\Adapter;

use ManaPHP\Caching\Cache;

/**
 * @property-read \Redis $redisCache
 */
class Redis extends Cache
{
    /**
     * @var string
     */
    protected $prefix;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['redisCache'])) {
            $this->injections['redisCache'] = $options['redisCache'];
        }

        $this->prefix = $options['prefix'] ?? sprintf('cache:%s:', APP_ID);
    }

    /**
     * @param string $key
     *
     * @return string|false
     */
    public function do_get($key)
    {
        return $this->redisCache->get($this->prefix . $key);
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
        $this->redisCache->set($this->prefix . $key, $value, $ttl);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function do_delete($key)
    {
        $this->redisCache->del($this->prefix . $key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function do_exists($key)
    {
        return (bool)$this->redisCache->exists($this->prefix . $key);
    }
}
