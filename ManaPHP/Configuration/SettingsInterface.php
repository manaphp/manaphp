<?php

namespace ManaPHP\Configuration;

interface SettingsInterface
{
    /**
     * @param string $key
     *
     * @return string
     */
    public function get($key);

    /**
     * @param array $keys
     *
     * @return array
     */
    public function mGet($keys);

    /**
     * @param string           $key
     * @param string|int|float $value
     *
     * @return static
     */
    public function set($key, $value);

    /**
     * @param array $kvs
     *
     * @return static
     */
    public function mSet($kvs);

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key);

    /**
     * @param string $key
     *
     * @return static
     */
    public function delete($key);
}