<?php

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Http\Cookies\Exception as CookiesException;

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

    public function reConstruct()
    {
        $this->_cookies = [];
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

        $this->_cookies[$name] = [
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
     * @param string $defaultValue
     *
     * @return mixed|null
     * @throws \ManaPHP\Http\Cookies\Exception
     */
    public function get($name, $defaultValue = null)
    {
        if ($name[0] === '!') {
            $name = (string)substr($name, 1);
            if (isset($_COOKIE[$name])) {
                return $this->_decrypt($name, $_COOKIE[$name]);
            }
        } else {
            if (isset($_COOKIE[$name])) {
                return $_COOKIE[$name];
            }
        }

        return $defaultValue;
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

        unset($_COOKIE[$name]);
        $this->_cookies[$name] = [
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

        $this->fireEvent('cookies:afterSend');
    }

    /**
     * @return array
     */
    public function getSent()
    {
        return $this->_cookies;
    }
}