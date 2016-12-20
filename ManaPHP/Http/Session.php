<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/19
 * Time: 15:52
 */
namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Http\Session\Exception as SessionException;

/**
 * Class ManaPHP\Http\Session
 *
 * @package session
 */
class Session extends Component implements SessionInterface, \ArrayAccess
{
    /**
     * @var \ManaPHP\Http\Session\AdapterInterface
     */
    public $adapter;

    /**
     * Session constructor.
     *
     * @param string|array|\ManaPHP\Http\Session\AdapterInterface $options
     *
     * @throws \ManaPHP\Http\Session\Exception
     */
    public function __construct($options = [])
    {
        if (is_string($options) || is_object($options)) {
            $options = ['adapter' => $options];
        }

        $this->adapter = $options['adapter'];
    }

    /**
     * @param \ManaPHP\DiInterface $dependencyInjector
     *
     * @return static
     * @throws \ManaPHP\Http\Session\Exception
     */
    public function setDependencyInjector($dependencyInjector)
    {
        parent::setDependencyInjector($dependencyInjector);

        if (!is_object($this->adapter)) {
            $this->adapter = $this->_dependencyInjector->getShared($this->adapter);
        }

        $open = [$this->adapter, 'open'];
        $close = [$this->adapter, 'close'];
        $read = [$this->adapter, 'read'];
        $write = [$this->adapter, 'write'];
        $destroy = [$this->adapter, 'destroy'];
        $gc = [$this->adapter, 'gc'];

        session_set_save_handler($open, $close, $read, $write, $destroy, $gc);

        if (PHP_SAPI !== 'cli' && !session_start()) {
            throw new SessionException('session start failed: :last_error_message');
        }

        return $this;
    }

    /**
     *
     */
    public function __destruct()
    {
        PHP_SAPI !== 'cli' && session_write_close();
    }

    /**
     * Gets a session variable from an application context
     *
     * @param string $name
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function get($name = null, $defaultValue = null)
    {
        if ($name === null) {
            return $_SESSION;
        } elseif (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        } else {
            return $defaultValue;
        }
    }

    /**
     * Sets a session variable in an application context
     *
     * @param string $name
     * @param mixed  $value
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
     * @return bool
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
     * @return string
     */
    public function getSessionId()
    {
        return session_id();
    }

    /**
     * Destroys the active session
     *
     * @return void
     * @throws \ManaPHP\Http\Session\Exception
     */
    public function destroy()
    {
        if (PHP_SAPI !== 'cli' && !session_destroy()) {
            throw new SessionException('destroy session failed: :last_error_message'/**m08409465b2b90d8a8*/);
        }
    }

    /**
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * @param mixed $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    /**
     * @return void
     */
    public function clean()
    {
        $this->adapter->clean();
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        $data = (isset($_SESSION) && is_array($_SESSION)) ? $_SESSION : [];

        $data['_internal_'] = ['adapter' => get_class($this->adapter)];
        return $data;
    }
}