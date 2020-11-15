<?php

namespace ManaPHP\Caching;

interface MCacheInterface
{
    /**
     * @param string   $key
     * @param callable $callback
     * @param int      $ttl
     *
     * @return mixed
     */
    public function get($key, $callback, $ttl = null);
}