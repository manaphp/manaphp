<?php
namespace ManaPHP;

/**
 * Interface IpcCacheInterface
 * @package ManaPHP
 */
interface IpcCacheInterface
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
     * @return  false
     */
    public function get($key);
}