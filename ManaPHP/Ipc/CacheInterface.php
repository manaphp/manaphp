<?php

namespace ManaPHP\Ipc;

/**
 * Interface CacheInterface
 *
 * @package ipc
 */
interface CacheInterface
{
    /**
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl
     */
    public function set($key, $value, $ttl);

    /**
     * @param string $key
     *
     * @return  mixed|false
     */
    public function get($key);
}