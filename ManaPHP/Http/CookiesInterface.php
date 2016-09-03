<?php

namespace ManaPHP\Http;

/**
 * ManaPHP\Http\Response\CookiesInterface initializer
 */
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
     * @param bool   $httpOnly
     *
     * @return static
     */
    public function set(
        $name,
        $value,
        $expire = 0,
        $path = null,
        $domain = null,
        $secure = false,
        $httpOnly = true
    );

    /**
     * Gets a cookie from the bag
     *
     * @param string $name
     *
     * @return $mixed
     */
    public function get($name);

    /**
     * Check if a cookie is defined in the bag or exists in the $_COOKIE
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name);

    /**
     * Deletes a cookie by its name
     * This method does not removes cookies from the $_COOKIE
     *
     * @param string $name
     *
     * @return bool
     */
    public function delete($name);

    /**
     * Sends the cookies to the client
     */
    public function send();
}