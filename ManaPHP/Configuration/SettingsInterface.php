<?php

namespace ManaPHP\Configuration;

interface SettingsInterface
{
    /**
     * @param string $key
     *
     * @return array
     */
    public function get($key);

    /**
     * @param string $key
     * @param array  $value
     *
     * @return static
     */
    public function set($key, $value);

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