<?php

namespace ManaPHP\Http;

interface CookiesInterface
{
    /**
     * @param string $name
     * @param mixed  $value
     * @param int    $expire
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     * @param bool   $httponly
     *
     * @return static
     */
    public function set($name, $value, $expire = 0, $path = null, $domain = null, $secure = false, $httponly = true);

    /**
     * @param string $name
     * @param string $default
     *
     * @return string
     */
    public function get($name, $default = '');

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name);

    /**
     * @param string $name
     * @param string $path
     * @param string $domain
     *
     * @return bool
     */
    public function delete($name, $path = null, $domain = null);
}