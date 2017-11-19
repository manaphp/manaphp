<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/19
 * Time: 15:52
 */
namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Component\ScopedCloneableInterface;
use ManaPHP\Http\Session\Exception as SessionException;

/**
 * Class ManaPHP\Http\Session
 *
 * @package session
 * @property \ManaPHP\Http\Cookies $cookies
 */
class Session extends Component implements SessionInterface, ScopedCloneableInterface, \ArrayAccess
{
    /**
     * @var \ManaPHP\Http\Session\AdapterInterface
     */
    public $adapter;

    /**
     * @var bool
     */
    protected $_started = false;

    /**
     * Session constructor.
     *
     * @param string|array|\ManaPHP\Http\Session\AdapterInterface $options
     *
     * @throws \ManaPHP\Http\Session\Exception
     */
    public function __construct($options = 'ManaPHP\Http\Session\Adapter\File')
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

        if (session_status() === PHP_SESSION_ACTIVE) {
            throw new SessionException('please call $this->session->start(), NOT session_start()');
        }

        $this->attachEvent('response:beforeSend');

        return $this;
    }

    public function onResponseBeforeSend()
    {
        PHP_SAPI !== 'cli' && $this->_started && session_write_close();
    }

    /**
     * @return static
     */
    public function start()
    {
        if (!$this->_started) {
            if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE && !session_start()) {
                /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                throw new SessionException('session start failed: :last_error_message');
            }

            $this->_started = true;
        }

        return $this;
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
        $this->_started || $this->start();

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
        $this->_started || $this->start();

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
        $this->_started || $this->start();

        return isset($_SESSION[$name]);
    }

    /**
     * Removes a session variable from an application context
     *
     * @param string $name
     */
    public function remove($name)
    {
        $this->_started || $this->start();

        unset($_SESSION[$name]);
    }

    /**
     * @return string
     */
    public function getId()
    {
        $this->_started || $this->start();

        return session_id();
    }

    /**
     * @param string $id
     *
     * @return void
     * @throws \ManaPHP\Http\Session\Exception
     */
    public function setId($id)
    {
        if ($this->_started) {
            throw new SessionException('session_id($id) needs to be called before session_start()');
        }

        session_id($id);

        $this->_started || $this->start();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return session_name();
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function setName($name)
    {
        return session_name($name);
    }

    /**
     * Destroys the active session
     *
     * @return void
     * @throws \ManaPHP\Http\Session\Exception
     */
    public function destroy()
    {
        $this->_started || $this->start();

        if (PHP_SAPI !== 'cli' && !session_destroy()) {
            throw new SessionException('destroy session failed: :last_error_message'/**m08409465b2b90d8a8*/);
        }

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            $this->cookies->delete(session_name(), $params['path'], $params['domain'], $params['secure'], $params['httponly']);
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

        $data['_internal_'] = ['adapter' => is_string($this->adapter) ? $this->adapter : get_class($this->adapter)];
        return $data;
    }

    /**
     * @param \ManaPHP\Component $scope
     *
     * @return static
     */
    public function getScopedClone($scope)
    {
        return $this->_dependencyInjector->getInstance('ManaPHP\Http\Session\Bag', $scope->getComponentName($this));
    }
}