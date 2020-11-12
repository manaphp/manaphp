<?php

namespace ManaPHP;

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