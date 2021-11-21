<?php

namespace ManaPHP\Http;

use ManaPHP\Component;

/**
 * @property-read \ManaPHP\Http\GlobalsInterface  $globals
 * @property-read \ManaPHP\Http\ResponseInterface $response
 */
class Cookies extends Component implements CookiesInterface
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
    public function set($name, $value, $expire = 0, $path = null, $domain = null, $secure = false, $httponly = true)
    {
        $this->globals->setCookie($name, $value);
        $this->response->setCookie($name, $value, $expire, $path, $domain, $secure, $httponly);

        return $this;
    }

    /**
     * @param string $name
     * @param string $default
     *
     * @return string
     */
    public function get($name, $default = '')
    {
        return $this->globals->getCookie()[$name] ?? $default;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return isset($this->globals->getCookie()[$name]);
    }

    /**
     * @param string $name
     * @param string $path
     * @param string $domain
     *
     * @return static
     */
    public function delete($name, $path = null, $domain = null)
    {
        $this->globals->unsetCookie($name);
        $this->response->setCookie($name, 'deleted', 1, $path, $domain);

        return $this;
    }
}