<?php
namespace ManaPHP\Http\Session\Adapter;

use ManaPHP\Http\Session;

/**
 * Class ManaPHP\Http\Session\Adapter\Redis
 *
 * @package session\adapter
 */
class Redis extends Session
{
    /**
     * @var string|\Redis
     */
    protected $_redis = 'redis';

    /**
     * @var string
     */
    protected $_prefix = 'session:';

    /**
     * Redis constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        if (isset($options['redis'])) {
            $this->_redis = $options['redis'];
        }

        if (isset($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }
    }

    /**
     * @param string $session_id
     *
     * @return string
     */
    public function do_read($session_id)
    {
        if (is_string($this->_redis)) {
            $this->_redis = $this->_di->getShared($this->_redis);
        }

        $data = $this->_redis->get($this->_prefix . $session_id);
        return is_string($data) ? $data : '';
    }

    /**
     * @param string $session_id
     * @param string $data
     * @param int    $ttl
     *
     * @return bool
     */
    public function do_write($session_id, $data, $ttl)
    {
        if (is_string($this->_redis)) {
            $this->_redis = $this->_di->getShared($this->_redis);
        }

        return $this->_redis->set($this->_prefix . $session_id, $data, $ttl);
    }

    /**
     * @param string $session_id
     * @param int    $ttl
     *
     * @return bool
     */
    public function do_touch($session_id, $ttl)
    {
        if (is_string($this->_redis)) {
            $this->_redis = $this->_di->getShared($this->_redis);
        }

        $this->_redis->setTimeout($session_id, $ttl);

        return true;
    }

    /**
     * @param string $session_id
     *
     * @return bool
     */
    public function do_destroy($session_id)
    {
        if (is_string($this->_redis)) {
            $this->_redis = $this->_di->getShared($this->_redis);
        }

        $this->_redis->delete($this->_prefix . $session_id);

        return true;
    }

    /**
     * @param int $ttl
     *
     * @return bool
     */
    public function do_gc($ttl)
    {
        return true;
    }
}