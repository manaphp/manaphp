<?php

namespace ManaPHP\Http;

use ArrayAccess;
use ManaPHP\Component;
use ManaPHP\Exception\NotSupportedException;

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

class SessionContext
{
    /**
     * @var int
     */
    public $ttl;

    /**
     * @var bool
     */
    public $started = false;

    /**
     * @var bool
     */
    public $is_new;

    /**
     * @var bool
     */
    public $is_dirty = false;

    /**
     * @var string
     */
    public $session_id;

    /**
     * @var array
     */
    public $_SESSION;
}

/**
 * Class ManaPHP\Http\Session
 *
 * @package session
 * @property-read \ManaPHP\Http\CookiesInterface $cookies
 * @property-read \ManaPHP\Http\RequestInterface $request
 * @property-read \ManaPHP\Http\SessionContext   $_context
 */
abstract class Session extends Component implements SessionInterface, ArrayAccess
{
    /**
     * @var int
     */
    protected $_ttl = 3600;

    /**
     * @var int
     */
    protected $_lazy;

    /**
     * @var string
     */
    protected $_name = 'PHPSESSID';

    /**
     * @var string
     */
    protected $_serializer = 'json';

    /**
     * @var array
     */
    protected $_params = ['expire' => 0, 'path' => null, 'domain' => null, 'secure' => false, 'httponly' => true];

    /**
     * Session constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['ttl'])) {
            $this->_ttl = (int)$options['ttl'];
        }

        if (isset($options['lazy'])) {
            $this->_lazy = (int)$options['lazy'];
        } else {
            $this->_lazy = (int)min($this->_ttl / 10, 600);
        }

        if (isset($options['name'])) {
            $this->_name = $options['name'];
        }

        if (isset($options['serializer'])) {
            $this->_serializer = $options['serializer'];
        }

        if (isset($options['params'])) {
            $this->_params = $options['params'] + $this->_params;
        }

        if (!isset($this->_params['path'])) {
            $this->_params['path'] = $this->alias->get('@web') ?: '/';
        }

        $this->attachEvent('response:sending', [$this, 'onResponseSending']);
    }

    /**
     * @return void
     */
    protected function _start()
    {
        $context = $this->_context;

        if ($context->started) {
            return;
        }

        $context->started = true;

        if (($session_id = $this->cookies->get($this->_name)) && ($str = $this->do_read($session_id))) {
            $context->is_new = false;

            if (is_array($data = $this->unserialize($str))) {
                $context->_SESSION = $data;
            } else {
                $context->_SESSION = [];
                $this->logger->error('unserialize failed', 'session.unserialize');
            }
        } else {
            $session_id = $this->_generateSessionId();
            $context->is_new = true;
            $context->_SESSION = [];
        }

        $context->session_id = $session_id;

        $this->fireEvent('session:start', $context);
    }

    public function onResponseSending()
    {
        $context = $this->_context;

        if (!$context->started) {
            return;
        }

        $this->fireEvent('session:end', $context);

        if ($context->is_new) {
            if (!$context->_SESSION) {
                return;
            }

            $params = $this->_params;
            $expire = $params['expire'] ? time() + $params['expire'] : 0;

            $this->cookies->set($this->_name, $context->session_id,
                $expire, $params['path'], $params['domain'], $params['secure'], $params['httponly']);

            $this->fireEvent('session:create', $context);
        } elseif ($context->is_dirty) {
            null;
        } elseif ($this->_lazy) {
            if (isset($context->_SESSION['__T']) && time() - $context->_SESSION['__T'] < $this->_lazy) {
                return;
            }
        } else {
            if ($this->do_touch($context->session_id, $context->ttl ?? $this->_ttl)) {
                return;
            }
        }

        $this->fireEvent('session:update', $context);

        if ($this->_lazy) {
            $context->_SESSION['__T'] = time();
        }

        $data = $this->serialize($context->_SESSION);
        if (!is_string($data)) {
            $this->logger->error('serialize data failed', 'session.serialize');
        }
        $this->do_write($context->session_id, $data, $context->ttl ?? $this->_ttl);
    }

    /**
     * Destroys the active session or assigned session
     *
     * @param string $session_id
     *
     * @return static
     */
    public function destroy($session_id = null)
    {
        if ($session_id) {
            $this->fireEvent('session:destroy', $session_id);
            $this->do_destroy($session_id);
        } else {
            $context = $this->_context;

            if (!$context->started) {
                $this->_start();
            }

            $this->fireEvent('session:destroy', $context);

            $context->started = false;
            $context->is_dirty = false;
            $context->session_id = null;
            $context->_SESSION = null;
            $this->do_destroy($context->session_id);

            $name = $this->_name;
            $params = $this->_params;
            $this->cookies->delete($name, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        return $this;
    }

    /**
     * @param string $session_id
     * @param int    $ttl
     *
     * @return bool
     */
    abstract public function do_touch($session_id, $ttl);

    /**
     * @param array $data
     *
     * @return string
     */
    public function serialize($data)
    {
        $serializer = $this->_serializer;

        //https://github.com/wikimedia/php-session-serializer/blob/master/src/Wikimedia/PhpSessionSerializer.php
        if ($serializer === 'php') {
            $r = '';
            foreach ($data as $key => $value) {
                $v = serialize($value);
                $r .= "$key|$v";
            }
            return $r;
        } elseif ($serializer === 'php_binary') {
            $r = '';
            foreach ($data as $key => $value) {
                $r .= chr(strlen($key)) . $key . serialize($value);
            }
            return $r;
        } elseif ($serializer === 'php_serialize') {
            return $this->serialize($data);
        } elseif ($serializer === 'json') {
            return json_stringify($data);
        } elseif ($serializer === 'igbinary') {
            return igbinary_serialize($data);
        } elseif ($serializer === 'wddx') {
            return wddx_serialize_value($data);
        } else {
            throw new NotSupportedException(['`:serializer` serializer is not support', 'serializer' => $serializer]);
        }
    }

    /**
     * @param string $data
     *
     * @return array|false
     */
    public function unserialize($data)
    {
        $serializer = $this->_serializer;

        if ($serializer === 'php') {
            $r = [];
            $offset = 0;
            while ($offset < strlen($data)) {
                if (!str_contains(substr($data, $offset), '|')) {
                    return false;
                }
                $pos = strpos($data, '|', $offset);
                $num = $pos - $offset;
                $key = substr($data, $offset, $num);
                $offset += $num + 1;
                $value = unserialize(substr($data, $offset), ['allowed_classes' => true]);
                $r[$key] = $value;
                $offset += strlen(serialize($value));
            }
            return $r;
        } elseif ($serializer === 'php_binary') {
            $r = [];
            $offset = 0;
            while ($offset < strlen($data)) {
                $num = ord($data[$offset]);
                $offset++;
                $key = substr($data, $offset, $num);
                $offset += $num;
                $value = unserialize(substr($data, $offset), ['allowed_classes' => true]);
                $r[$key] = $value;
                $offset += strlen(serialize($value));
            }
            return $r;
        } elseif ($serializer === 'php_serialize') {
            return unserialize($data, ['allowed_classes' => true]);
        } elseif ($serializer === 'json') {
            return json_parse($data);
        } elseif ($serializer === 'igbinary') {
            return igbinary_unserialize($data);
        } elseif ($serializer === 'wddx') {
            return wddx_deserialize($data);
        } else {
            throw new NotSupportedException(['`:serializer` serializer is not support', 'serializer' => $serializer]);
        }
    }

    /**
     * @param string $session_id
     *
     * @return string
     */
    abstract public function do_read($session_id);

    /**
     * @param string $session_id
     * @param string $data
     * @param int    $ttl
     *
     * @return bool
     */
    abstract public function do_write($session_id, $data, $ttl);

    abstract public function do_gc($ttl);

    /**
     * @return string
     */
    protected function _generateSessionId()
    {
        return $this->random->getBase(32, 36);
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
        $context = $this->_context;

        if (!$context->started) {
            $this->_start();
        }

        if ($name === null) {
            return $context->_SESSION;
        } elseif (isset($context->_SESSION[$name])) {
            return $context->_SESSION[$name];
        } else {
            return $default;
        }
    }

    /**
     * Sets a session variable in an application context
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return static
     */
    public function set($name, $value)
    {
        $context = $this->_context;

        if (!$context->started) {
            $this->_start();
        }

        $context->is_dirty = true;
        $context->_SESSION[$name] = $value;

        return $this;
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
        $context = $this->_context;

        if (!$context->started) {
            $this->_start();
        }

        return isset($context->_SESSION[$name]);
    }

    /**
     * Removes a session variable from an application context
     *
     * @param string $name
     *
     * @return static
     */
    public function remove($name)
    {
        $context = $this->_context;

        if (!$context->started) {
            $this->_start();
        }

        $context->is_dirty = true;
        unset($context->_SESSION[$name]);

        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        $context = $this->_context;

        if (!$context->started) {
            $this->_start();
        }

        return $context->session_id;
    }

    /**
     * @param string $id
     *
     * @return static
     */
    public function setId($id)
    {
        $context = $this->_context;

        if (!$context->started) {
            $this->_start();
        }

        $context->session_id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getTtl()
    {
        return $this->_context->ttl ?? $this->_ttl;
    }

    /**
     * @param int $ttl
     *
     * @return static
     */
    public function setTtl($ttl)
    {
        $this->_context->ttl = $ttl;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    abstract public function do_destroy($session_id);

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
     * @param string $session_id
     *
     * @return array
     */
    public function read($session_id)
    {
        $session = $this->do_read($session_id);
        if (!$session) {
            return [];
        }

        return $this->unserialize($session);
    }

    /**
     * @param string $session_id
     * @param array  $data
     *
     * @return static
     */
    public function write($session_id, $data)
    {
        $session = $this->serialize($data);

        $this->do_write($session_id, $session, $this->_context->ttl ?? $this->_ttl);

        return $this;
    }
}