<?php

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Http\Cookies\Exception as CookiesException;

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
 * @property-read \ManaPHP\Security\CryptInterface $crypt
 * @property \ManaPHP\Http\CookiesContext          $_context
 */
class Cookies extends Component implements CookiesInterface
{
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
        $this->_configureContext('ManaPHP\Http\CookiesContext');

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
    public function set($name, $value, $expire = 0, $path = null, $domain = null, $secure = false, $httpOnly = true)
    {
        $context = $this->_context;

        if ($expire > 0) {
            $current = time();
            if ($expire < $current) {
                $expire += $current;
            }
        }

        if ($name[0] === '!') {
            $name = (string)substr($name, 1);
            $value = $this->_encrypt($name, $value);
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

        $_COOKIE[$name] = $value;

        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     *
     * @return string
     * @throws \ManaPHP\Http\Cookies\Exception
     */
    protected function _decrypt($name, $value)
    {
        if ($this->_key === null) {
            $this->_key = $this->crypt->getDerivedKey('cookies');
        }

        $data = $this->crypt->decrypt(base64_decode($value), $this->_key . $name);
        $json = json_decode($data, true);
        if (!is_array($json) || !isset($json['value'])) {
            throw new CookiesException('cookie value is corrupted');
        }

        return $json['value'];
    }

    /**
     * @param string $name
     * @param string $value
     *
     * @return string
     */
    protected function _encrypt($name, $value)
    {
        if ($this->_key === null) {
            $this->_key = $this->crypt->getDerivedKey('cookies');
        }

        return base64_encode($this->crypt->encrypt(json_encode(['value' => $value]), $this->_key . $name));
    }

    /**
     * Gets a cookie
     *
     * @param string $name
     * @param string $default
     *
     * @return mixed|null
     * @throws \ManaPHP\Http\Cookies\Exception
     */
    public function get($name, $default = null)
    {
        $context = $this->_context;

        if ($name === null) {
            return $context->cookies;
        } elseif ($name[0] === '!') {
            $name = (string)substr($name, 1);
            if (isset($_COOKIE[$name])) {
                return $this->_decrypt($name, $_COOKIE[$name]);
            }
        } elseif (isset($_COOKIE[$name])) {
            return $_COOKIE[$name];
        }

        return $default;
    }

    /**
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
        $context = $this->_context;

        if ($name[0] === '!') {
            $name = (string)substr($name, 1);
        }

        unset($_COOKIE[$name]);
        $context->cookies[$name] = [
            'name' => $name,
            'value' => 'deleted',
            'expire' => 1,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httpOnly' => $httpOnly
        ];

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

        $this->fireEvent('cookies:beforeSend');

        foreach ($context->cookies as $cookie) {
            setcookie($cookie['name'], $cookie['value'], $cookie['expire'],
                $cookie['path'], $cookie['domain'], $cookie['secure'],
                $cookie['httpOnly']);
        }

        $this->fireEvent('cookies:afterSend');
    }

    /**
     * @return array
     */
    public function getSent()
    {
        return $this->_context->cookies;
    }
}