<?php

namespace ManaPHP\Http {

    use ManaPHP\Component;
    use ManaPHP\Http\Cookies\Exception;

    /**
     * ManaPHP\Http\Cookies
     *
     * This class is a bag to manage the cookies
     */
    class Cookies extends Component implements CookiesInterface
    {
        /**
         * @var array
         */
        protected $_cookies=[];

        /**
         * @var array
         */
        protected $_deletedCookies=[];

        /**
         * Sets a cookie to be sent at the end of the request
         *
         * @param string  $name
         * @param mixed   $value
         * @param int     $expire
         * @param string  $path
         * @param string  $domain
         * @param boolean $secure
         * @param boolean $httpOnly
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
            $cookie = [
                'expire' => $expire,
                'path' => $path,
                'domain' => $domain,
                'secure' => $secure,
                'httpOnly' => $httpOnly
            ];

            if ($name[0] === '!') {
                $name = substr($name, 1);
                $value=$this->_encrypt($value);
            }

            $cookie['name'] = $name;
            $cookie['value'] = $value;

            $this->_cookies[$name] = $cookie;
            $_COOKIE[$name] = $value;
            unset($this->_deletedCookies[$name]);

            return $this;
        }

        /**
         * @param $value
         *
         * @return mixed
         */
        protected function _decrypt($value)
        {
            return base64_decode($this->crypt->decrypt($value));
        }

        /**
         * @param $value
         *
         * @return mixed
         */
        protected function _encrypt($value)
        {
            return base64_encode($this->crypt->encrypt($value));
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
                $name = substr($name, 1);
                if(isset($_COOKIE[$name])){
                    return $this->_decrypt($_COOKIE[$name]);
                }

            } else {
                if(isset($_COOKIE[$name])){
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
         * @return boolean
         */
        public function has($name)
        {
            if ($name[0] === '!') {
                $name = substr($name, 1);
            }

            return isset($_COOKIE[$name]);
        }

        /**
         * Deletes a cookie by its name
         *
         * @param string $name
         *
         * @return static
         */
        public function delete($name)
        {
            if ($name[0] === '!') {
                $name = substr($name, 1);
            }

            unset($this->_cookies[$name], $_COOKIE[$name]);
            $this->_deletedCookies[$name] = null;

            return $this;
        }

        /**
         * Sends the cookies to the client
         * Cookies are not sent if headers are sent in the current request
         *
         * @throws \ManaPHP\Http\Cookies\Exception
         */
        public function send()
        {
            if(headers_sent($file,$line)){
                throw new Exception("Headers has been sent in $file:$line");
            }

            foreach ($this->_cookies as $cookie) {
                setcookie($cookie['name'], $cookie['value'], $cookie['expire'],
                    $cookie['path'], $cookie['domain'], $cookie['secure'],
                    $cookie['httpOnly']);
            }

            foreach ($this->_deletedCookies as $cookie => $_) {
                setcookie($cookie);
            }
        }
    }
}
