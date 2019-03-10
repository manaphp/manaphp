<?php
namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Http\Session\Exception as SessionException;

/**
 * Class ManaPHP\Http\Session
 *
 * @package session
 * @property-read \ManaPHP\Http\CookiesInterface $cookies
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
abstract class Session extends Component implements SessionInterface, \ArrayAccess
{
    /**
     * @var int
     */
    protected $_ttl;

    /**
     * @var bool
     */
    protected $_started = false;

    /**
     * @var string
     */
    protected $_session_name;

    /**
     * Session constructor.
     *
     * @param array $options
     *
     * @throws \ManaPHP\Http\Session\Exception
     */
    public function __construct($options = [])
    {
        if (isset($options['ttl'])) {
            $this->_ttl = (int)$options['ttl'];
        }

        if (!is_int($this->_ttl)) {
            $this->_ttl = (int)ini_get('session.gc_maxlifetime');
        }

        /** @noinspection PhpParamsInspection */
        session_set_save_handler(
            [$this, '_handler_open'], [$this, '_handler_close'],
            [$this, '_handler_read'], [$this, '_handler_write'],
            [$this, '_handler_destroy'], [$this, '_handler_gc']);

        if (session_status() === PHP_SESSION_ACTIVE) {
            throw new SessionException('session_start() has been called, use session component is too late');
        }

        $this->attachEvent('response:beforeSend', [$this, 'onBeforeSendResponse']);
    }

    public function restoreInstanceState($data)
    {
        if ($this->_started) {
            $this->save();
            $_SESSION = [];
        }

        $this->_started = false;
    }

    /**
     * @ignore
     */
    public function onBeforeSendResponse()
    {
        $this->save();
    }

    /**
     * @ignore
     *
     * @param string $save_path
     * @param string $session_name
     *
     * @return bool
     */
    public function _handler_open($save_path, $session_name)
    {
        $save_path && $session_name;
        return true;
    }

    /**
     * @ignore
     *
     * @return bool
     */
    public function _handler_close()
    {
        return true;
    }

    /**
     * @param string $session_id
     *
     * @return string
     */
    abstract public function do_read($session_id);

    /**
     * @ignore
     *
     * @param string $session_id
     *
     * @return string
     */
    public function _handler_read($session_id)
    {
        return $this->do_read($session_id);
    }

    /**
     * @param string $session_id
     * @param string $data
     * @param int    $ttl
     *
     * @return bool
     */
    abstract public function do_write($session_id, $data, $ttl);

    /**
     * @ignore
     *
     * @param $session_id
     * @param $data
     *
     * @return bool
     */
    public function _handler_write($session_id, $data)
    {
        return $this->do_write($session_id, $data, $this->_ttl);
    }

    /**
     * @ignore
     *
     * @param string $session_id
     *
     * @return bool
     */
    public function _handler_destroy($session_id)
    {
        return $this->do_destroy($session_id);
    }

    abstract public function do_gc($ttl);

    /**
     * @ignore
     *
     * @param int $ttl
     *
     * @return bool
     */
    public function _handler_gc($ttl)
    {
        return $this->do_gc($ttl);
    }

    /**
     * @return void
     */
    protected function _start()
    {
        if (!$this->_started) {
            if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE && !session_start()) {
                /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                throw new SessionException('session start failed: :last_error_message');
            }

            $this->_started = true;
        }
    }

    /**
     * Gets a session variable from an application context
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($name = null, $default = null)
    {
        $this->_started || $this->_start();

        if ($name === null) {
            return $_SESSION;
        } elseif (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        } else {
            return $default;
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
        $this->_started || $this->_start();

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
        $this->_started || $this->_start();

        return isset($_SESSION[$name]);
    }

    /**
     * Removes a session variable from an application context
     *
     * @param string $name
     */
    public function remove($name)
    {
        $this->_started || $this->_start();

        unset($_SESSION[$name]);
    }

    /**
     * @return string
     */
    public function getId()
    {
        $this->_started || $this->_start();

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
            throw new SessionException('setId($id) needs to be called before session_start()');
        }

        session_id($id);

        $this->_started || $this->_start();
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

    abstract public function do_destroy($session_id);

    /**
     * Destroys the active session or assigned session
     *
     * @param string $session_id
     *
     * @return void
     * @throws \ManaPHP\Http\Session\Exception
     */
    public function destroy($session_id = null)
    {
        if ($session_id) {
            $this->do_destroy($session_id);
        } else {
            $this->_started || $this->_start();

            if (PHP_SAPI !== 'cli' && !session_destroy()) {
                throw new SessionException('destroy session failed: :last_error_message');
            }

            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                $this->cookies->delete(session_name(), $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
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
     * Force the session to be saved and closed.
     *
     * This method is generally not required for real sessions as the session will be automatically saved at the end of code execution.
     */
    public function save()
    {
        if ($this->_started) {
            $this->_started = false;
            session_write_close();
        }
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        $this->_started || $this->_start();

        return $_SESSION;
    }
}