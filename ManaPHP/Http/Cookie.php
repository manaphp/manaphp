<?php

namespace ManaPHP\Http {

    use ManaPHP\Component;

    /**
     * ManaPHP\Http\Cookie
     *
     * Provide OO wrappers to manage a HTTP cookie
     */
    class Cookie extends Component
    {
        protected $_hasRead;

        protected $_restored;

        protected $_useEncryption;

        protected $_filter;

        /**
         * @var string
         */
        protected $_name;

        protected $_value;

        protected $_expire;

        protected $_path;

        protected $_domain;

        protected $_secure;

        protected $_httpOnly;

        /**
         * \ManaPHP\Http\Cookie constructor
         *
         * @param string  $name
         * @param mixed   $value
         * @param int     $expire
         * @param string  $path
         * @param boolean $secure
         * @param string  $domain
         * @param boolean $httpOnly
         */
        public function __construct(
            $name,
            $value = null,
            $expire = null,
            $path = null,
            $secure = null,
            $domain = null,
            $httpOnly = null
        ) {
            $this->_name = $name;
            $this->_value = $value;
            $this->_expire = $expire;
            $this->_path = $path;
            $this->_secure = $secure;
            $this->_domain = $domain;
            $this->_httpOnly = $httpOnly;
        }

        /**
         * Sets the cookie's value
         *
         * @param string $value
         *
         * @return \ManaPHP\Http\Cookie
         */
        public function setValue($value)
        {
            $this->_value = $value;
        }

        /**
         * Returns the cookie's value
         *
         * @param string|array $filters
         * @param string       $defaultValue
         *
         * @return mixed
         */
        public function getValue($filters = null, $defaultValue = null)
        {

        }

        /**
         * Sends the cookie to the HTTP client
         * Stores the cookie definition in session
         *
         * @return static
         */
        public function send()
        {
        }

        /**
         * Reads the cookie-related info from the SESSION to restore the cookie as it was set
         * This method is automatically called internally so normally you don't need to call it
         *
         * @return static
         */
        public function restore()
        {
        }

        /**
         * Deletes the cookie by setting an expire time in the past
         *
         */
        public function delete()
        {
        }

        /**
         * Sets if the cookie must be encrypted/decrypted automatically
         *
         * @param boolean $useEncryption
         *
         * @return static
         */
        public function useEncryption($useEncryption)
        {
        }

        /**
         * Check if the cookie is using implicit encryption
         *
         * @return boolean
         */
        public function isUsingEncryption()
        {
        }

        /**
         * Sets the cookie's expiration time
         *
         * @param int $expire
         *
         * @return static
         */
        public function setExpiration($expire)
        {
        }

        /**
         * Returns the current expiration time
         *
         * @return string
         */
        public function getExpiration()
        {
        }

        /**
         * Sets the cookie's expiration time
         *
         * @param string $path
         *
         * @return static
         */
        public function setPath($path)
        {
        }

        /**
         * Returns the current cookie's path
         *
         * @return string
         */
        public function getPath()
        {
        }

        /**
         * Sets the domain that the cookie is available to
         *
         * @param string $domain
         *
         * @return static
         */
        public function setDomain($domain)
        {
        }

        /**
         * Returns the domain that the cookie is available to
         *
         * @return string
         */
        public function getDomain()
        {
        }

        /**
         * Sets if the cookie must only be sent when the connection is secure (HTTPS)
         *
         * @param boolean $secure
         *
         * @return static
         */
        public function setSecure($secure)
        {
        }

        /**
         * Returns whether the cookie must only be sent when the connection is secure (HTTPS)
         *
         * @return boolean
         */
        public function getSecure()
        {
        }

        /**
         * Sets if the cookie is accessible only through the HTTP protocol
         *
         * @param boolean $httpOnly
         *
         * @return static
         */
        public function setHttpOnly($httpOnly)
        {
        }

        /**
         * Returns if the cookie is accessible only through the HTTP protocol
         *
         * @return boolean
         */
        public function getHttpOnly()
        {
        }

        /**
         * Magic __toString method converts the cookie's value to string
         *
         * @return mixed
         */
        public function __toString()
        {
        }

    }
}
