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
     * @return \Redis
     */
    protected function _getRedis()
    {
        if (strpos($this->_redis, '/') !== false) {
            return $this->_redis = $this->_di->get('ManaPHP\Redis', [$this->_redis]);
        } else {
            return $this->_redis = $this->_di->getShared($this->_redis);
        }
    }

    /**
     * @param string $session_id
     *
     * @return string
     */
    public function do_read($session_id)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        $data = $redis->get($this->_prefix . $session_id);
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
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        return $redis->set($this->_prefix . $session_id, $data, $ttl);
    }

    /**
     * @param string $session_id
     * @param int    $ttl
     *
     * @return bool
     */
    public function do_touch($session_id, $ttl)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        $redis->setTimeout($session_id, $ttl);

        return true;
    }

    /**
     * @param string $session_id
     *
     * @return bool
     */
    public function do_destroy($session_id)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        $redis->delete($this->_prefix . $session_id);

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