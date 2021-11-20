<?php

namespace ManaPHP;

interface EnvInterface
{
    /**
     * @return static
     */
    public function load();

    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed|array
     */
    public function get($key, $default = null);
}