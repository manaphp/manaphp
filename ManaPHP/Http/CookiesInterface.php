<?php

namespace ManaPHP\Http;

interface CookiesInterface
{
    /**
     * Sets a cookie to be sent at the end of the request
     *
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
     * Gets a cookie from the bag
     *
     * @param string $name
     * @param string $default
     *
     * @return mixed
     */
    public function get($name = null, $default = '');

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name);

    /**
     * Deletes a cookie by its name
     *
     * @param string $name
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     * @param bool   $httponly
     *
     * @return bool
     */
    public function delete($name, $path = null, $domain = null, $secure = false, $httponly = true);
}