<?php

namespace ManaPHP\Caching;

/**
 * Interface ManaPHP\Caching\CacheInterface
 *
 * @package cache
 */
interface CacheInterface
{
    /**
     * Checks whether a specified key exists in the cache.
     *
     * @param string $key
     *
     * @return bool
     */
    public function exists($key);

    /**
     * Retrieves a value from cache with a specified key.
     *
     * @param string $key
     *
     * @return string|false
     */
    public function get($key);

    /**
     * Stores a value identified by a key into cache.
     *
     * @param string $key
     * @param string $value
     * @param int    $ttl
     *
     * @return void
     */
    public function set($key, $value, $ttl);

    /**
     * Deletes a value with the specified key from cache
     *
     * @param string $key
     *
     * @void
     */
    public function delete($key);

    /**
     * @param string   $key
     * @param int      $ttl
     * @param callable $callback
     *
     * @return mixed
     */
    public function remember($key, $ttl, $callback);
}