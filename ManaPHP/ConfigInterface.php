<?php

namespace ManaPHP;

interface ConfigInterface
{
    /**
     * @param string $file
     *
     * @return array
     */
    public function load($file = '@config/app.php');

    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($key = null, $default = null);

    /**
     * @param string $key
     * @param mixed value
     *
     * @return mixed
     */
    public function set($key, $value);

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has($key);
}