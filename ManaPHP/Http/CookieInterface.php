<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/11/27
 * Time: 23:06
 */
namespace ManaPHP\Http {

    /**
     * ManaPHP\Http\CookieInterface
     *
     * Interface for ManaPHP\Http\Cookie
     */
    interface CookieInterface
    {
        /**
         * Sets the cookie's value
         *
         * @param string $value
         *
         * @return static
         */
        public function setValue($value);

        /**
         * Returns the cookie's value
         *
         * @param string|array $filters
         * @param string       $defaultValue
         *
         * @return mixed
         */
        public function getValue($filters = null, $defaultValue = null);

        /**
         * Sends the cookie to the HTTP client
         *
         * @return static
         */
        public function send();

        /**
         * Deletes the cookie
         *
         * @return static
         */
        public function delete();

        /**
         * Sets if the cookie must be encrypted/decrypted automatically
         *
         * @param boolean $useEncryption
         *
         * @return static
         */
        public function useEncryption($useEncryption);

        /**
         * Check if the cookie is using implicit encryption
         *
         * @return boolean
         */
        public function isUsingEncryption();

        /**
         * Sets the cookie's expiration time
         *
         * @param int $expire
         *
         * @return static
         */
        public function setExpiration($expire);

        /**
         * Sets the cookie's expiration time
         *
         * @param string $path
         *
         * @return static
         */
        public function setPath($path);

        /**
         * Returns the current cookie's name
         *
         * @return string
         */
        public function getName();

        /**
         * Sets the domain that the cookie is available to
         *
         * @param string $domain
         *
         * @return static
         */
        public function setDomain($domain);

        /**
         * Sets if the cookie must only be sent when the connection is secure (HTTPS)
         *
         * @param boolean $secure
         *
         * @return static
         */
        public function setSecure($secure);

        /**
         * Sets if the cookie is accessible only through the HTTP protocol
         *
         * @param boolean $httpOnly
         *
         * @return static
         */
        public function setHttpOnly($httpOnly);

    }

}