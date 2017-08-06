<?php

namespace ManaPHP\Http;

use ManaPHP\Component;

/**
 * Class ManaPHP\Http\Cookies
 *
 * @package cookies
 *
 * @property \ManaPHP\Security\CryptInterface $crypt
 */
class Cookies extends Component implements CookiesInterface
{
    /**
     * @var array
     */
    protected $_cookies = [];

    /**
     * @var array
     */
    protected $_deletedCookies = [];

    /**
     * @var string
     */
    protected $_key;

    /**
     * Cookies constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = [])
    {
        if (is_string($options)) {
            $options = ['key' => $options];
        }

        if (isset($options['key'])) {
            $this->_key = $options['key'];
        }
    }

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
    ) {
        if (PHP_SAPI !== 'cli' && headers_sent($file, $line)) {
            trigger_error("Headers has been sent in $file:$line", E_USER_WARNING);
        }

        if ($expire) {
            $current = time();
            if ($expire < $current) {
                $expire += $current;
            }
        }

        $cookie = [
            'expire' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httpOnly' => $httpOnly
        ];

        if ($name[0] === '!') {
            $name = (string)substr($name, 1);
            $value = $this->_encrypt($value);
        }

        $cookie['name'] = $name;
        $cookie['value'] = $value;

        $this->_cookies[$name] = $cookie;
        $_COOKIE[$name] = $value;
        unset($this->_deletedCookies[$name]);

        return $this;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function _decrypt($value)
    {
        if ($this->_key === null) {
            $this->_key = $this->crypt->getDerivedKey('cookies');
        }

        return $this->crypt->decrypt(base64_decode($value), $this->_key);
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function _encrypt($value)
    {
        if ($this->_key === null) {
            $this->_key = $this->crypt->getDerivedKey('cookies');
        }

        return base64_encode($this->crypt->encrypt($value, $this->_key));
    }

    /**
     * Gets a cookie
     *
     * @param string $name
     *
     * @return mixed|null
     */
    public function get($name)
    {
        if ($name[0] === '!') {
            $name = (string)substr($name, 1);
            if (isset($_COOKIE[$name])) {
                return $this->_decrypt($_COOKIE[$name]);
            }

        } else {
            if (isset($_COOKIE[$name])) {
                return $_COOKIE[$name];
            }
        }

        return null;
    }

    /**
     * Check if a cookie is defined in the bag or exists in the $_COOKIE
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        if ($name[0] === '!') {
            $name = (string)substr($name, 1);
        }

        return isset($_COOKIE[$name]);
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
        if ($name[0] === '!') {
            $name = (string)substr($name, 1);
        }

        unset($this->_cookies[$name], $_COOKIE[$name]);
        $this->_deletedCookies[$name] = ['path' => $path, 'domain' => $domain, 'secure' => $secure, 'httpOnly' => $httpOnly];

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
        $file = null;
        $line = null;

        $this->fireEvent('cookies:beforeSend');

        if (headers_sent($file, $line)) {
            trigger_error("Headers has been sent in $file:$line", E_USER_WARNING);
        }

        foreach ($this->_cookies as $cookie) {
            setcookie($cookie['name'], $cookie['value'], $cookie['expire'],
                $cookie['path'], $cookie['domain'], $cookie['secure'],
                $cookie['httpOnly']);
        }

        foreach ($this->_deletedCookies as $cookie => $param) {
            setcookie($cookie, '', 0, $param['path'], $param['domain'], $param['secure'], $param['httpOnly']);
        }

        $this->fireEvent('cookies:afterSend');
    }
}