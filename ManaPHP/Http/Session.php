<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/19
 * Time: 15:52
 */
namespace ManaPHP\Http {

    use ManaPHP\Http\Session\Exception;

    /**
     * ManaPHP\Http\Session\AdapterInterface initializer
     */
    class Session implements SessionInterface, \ArrayAccess
    {

        public function __construct($options = null)
        {
            if (PHP_SAPI === 'cli') {
                return;
            }

            session_start();

            $message = error_get_last()['message'];
            if (strpos($message, 'session_start():') === 0) {
                throw new Exception($message);
            }
        }

        public function __destruct()
        {
            session_write_close();
        }

        /**
         * Gets a session variable from an application context
         *
         * @param string $name
         * @param mixed  $defaultValue
         *
         * @return mixed
         */
        public function get($name, $defaultValue = null)
        {
            if (isset($_SESSION[$name])) {
                return $_SESSION[$name];
            } else {
                return $defaultValue;
            }
        }

        /**
         * Sets a session variable in an application context
         *
         * @param string $name
         * @param string $value
         */
        public function set($name, $value)
        {
            $_SESSION[$name] = $value;
        }

        /**
         * Check whether a session variable is set in an application context
         *
         * @param string $name
         *
         * @return boolean
         */
        public function has($name)
        {
            return isset($_SESSION[$name]);
        }

        /**
         * Removes a session variable from an application context
         *
         * @param string $name
         */
        public function remove($name)
        {
            unset($_SESSION[$name]);
        }

        /**
         * Destroys the active session
         *
         * @return boolean
         * @throws \ManaPHP\Http\Session\Exception
         */
        public function destroy()
        {
            if (PHP_SAPI === 'cli') {
                return;
            }

            if (!session_destroy()) {
                throw new Exception(error_get_last()['message']);
            }
        }

        public function offsetExists($offset)
        {
            return $this->has($offset);
        }

        public function offsetGet($offset)
        {
            return $this->get($offset);
        }

        public function offsetSet($offset, $value)
        {
            $this->set($offset, $value);
        }

        public function offsetUnset($offset)
        {
            $this->remove($offset);
        }
    }
}