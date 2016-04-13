<?php

namespace ManaPHP\Http\Response {

    use ManaPHP\Component;

    /**
     * ManaPHP\Http\Response\Cookies
     *
     * This class is a bag to manage the cookies
     * A cookies bag is automatically registered as part of the 'response' service in the DI
     */
    class Cookies extends Component implements CookiesInterface
    {
        protected $_registered;

        protected $_useEncryption;

        /**
         * @var \ManaPHP\Http\Cookie[]
         */
        protected $_cookies;

        /**
         * Set if cookies in the bag must be automatically encrypted/decrypted
         *
         * @param boolean $useEncryption
         *
         * @return static
         */
        public function useEncryption($useEncryption)
        {
            $this->_useEncryption = $useEncryption;

            return $this;
        }

        /**
         * Returns if the bag is automatically encrypting/decrypting cookies
         *
         * @return boolean
         */
        public function isUsingEncryption()
        {
            return $this->_useEncryption;
        }

        /**
         * Sets a cookie to be sent at the end of the request
         * This method overrides any cookie set before with the same name
         *
         * @param string  $name
         * @param mixed   $value
         * @param int     $expire
         * @param string  $path
         * @param boolean $secure
         * @param string  $domain
         * @param boolean $httpOnly
         *
         * @return static
         * @throws \ManaPHP\Http\Response\Exception
         */
        public function set(
            $name,
            $value = null,
            $expire = null,
            $path = null,
            $secure = null,
            $domain = null,
            $httpOnly = null
        ) {
            if (!isset($this->_cookies[$name])) {

                $cookie = $this->_dependencyInjector->get('ManaPHP\Http\Cookie',
                    [$name, $value, $expire, $path, $secure, $domain, $httpOnly]);

                $cookie->setDependencyInjector($this->_dependencyInjector);
                $cookie->useEncryption($this->_useEncryption);
                $this->_cookies[$name] = $cookie;
            } else {
                $cookie = $this->_cookies[$name];

                $cookie->setValue($value);
                $cookie->setExpiration($expire);
                $cookie->setPath($path);
                $cookie->setSecure($secure);
                $cookie->setDomain($domain);
                $cookie->setHttpOnly($httpOnly);
            }

            if ($this->_registered === false) {
                if (!is_object($this->_dependencyInjector)) {
                    throw new Exception("A dependency injection object is required to access the 'response' service");
                }

                $response = $this->_dependencyInjector->getShared('response');
                $response->setCookies($this);
            }

            return $this;
        }

        /**
         * Gets a cookie from the bag
         *
         * @param string $name
         *
         * @return \ManaPHP\Http\Cookie
         */
        public function get($name)
        {
            if (isset($this->_cookies[$name])) {
                return $this->_cookies[$name];
            }

            $cookie = $this->_dependencyInjector->get('ManaPHP\Http\Cookie', [$name]);
            if (is_object($this->_dependencyInjector)) {
                $cookie->setDependencyInjector($this->_dependencyInjector);
                $cookie->useEncryption($this->_useEncryption);
            }
            $this->_cookies[$name] = $cookie;

            return $cookie;
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
            if (isset($this->_cookies[$name])) {
                return true;
            }

            if (isset($_COOKIE[$name])) {
                return true;
            }

            return false;
        }

        /**
         * Deletes a cookie by its name
         * This method does not removes cookies from the $_COOKIE
         *
         * @param string $name
         *
         * @return boolean
         */
        public function delete($name)
        {
            if (isset($this->_cookies[$name])) {
                $this->_cookies[$name]->delete();

                return true;
            } else {
                return false;
            }
        }

        /**
         * Sends the cookies to the client
         * Cookies are not sent if headers are sent in the current request
         *
         * @return boolean
         */
        public function send()
        {
            if (!headers_sent()) {
                foreach ($this->_cookies as $cookie) {
                    $cookie->send();
                }

                return true;
            } else {
                return false;
            }
        }

        /**
         * Reset set cookies
         *
         * @return static
         */
        public function reset()
        {
            $this->_cookies = [];

            return $this;
        }
    }
}
