<?php

namespace ManaPHP\Http;

use ManaPHP\Component;

class CookiesContext
{
    /**
     * @var array
     */
    public $cookies = [];
}

/**
 * Class ManaPHP\Http\Cookies
 *
 * @package cookies
 *
 * @property-read \ManaPHP\Http\RequestInterface $request
 * @property \ManaPHP\Http\CookiesContext        $_context
 */
class Cookies extends Component implements CookiesInterface
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
    public function set($name, $value, $expire = 0, $path = null, $domain = null, $secure = false, $httpOnly = true)
    {
        $context = $this->_context;

        if ($expire > 0) {
            $current = time();
            if ($expire < $current) {
                $expire += $current;
            }
        }

        $context->cookies[$name] = [
            'name' => $name,
            'value' => $value,
            'expire' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httpOnly' => $httpOnly
        ];

        $globals = $this->request->getGlobals();

        $globals->_COOKIE[$name] = $value;

        return $this;
    }

    /**
     * Gets a cookie
     *
     * @param string $name
     * @param string $default
     *
     * @return mixed|null
     */
    public function get($name, $default = null)
    {
        $globals = $this->request->getGlobals();

        if ($name === null) {
            return $globals->_COOKIE;
        } elseif (isset($globals->_COOKIE[$name])) {
            return $globals->_COOKIE[$name];
        } else {
            return $default;
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        $globals = $this->request->getGlobals();

        return isset($globals->_COOKIE[$name]);
    }

    /**
     * Deletes a cookie by its name
     *
     * @param string $name
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     * @param bool   $httpOnly
     *
     * @return static
     */
    public function delete($name, $path = null, $domain = null, $secure = false, $httpOnly = true)
    {
        $context = $this->_context;

        $context->cookies[$name] = [
            'name' => $name,
            'value' => 'deleted',
            'expire' => 1,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httpOnly' => $httpOnly
        ];

        $globals = $this->request->getGlobals();

        unset($globals->_COOKIE[$name]);

        return $this;
    }

    /**
     * Sends the cookies to the client
     * Cookies are not sent if headers are sent in the current request
     *
     * @return void
     */
    public function send()
    {
        $context = $this->_context;

        foreach ($context->cookies as $cookie) {
            setcookie($cookie['name'], $cookie['value'], $cookie['expire'],
                $cookie['path'], $cookie['domain'], $cookie['secure'],
                $cookie['httpOnly']);
        }
    }
}