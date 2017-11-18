<?php

namespace ManaPHP\Cache\Engine;

use ManaPHP\Cache\EngineInterface;
use ManaPHP\Component;
use ManaPHP\Di;

/**
 * Class ManaPHP\Cache\Adapter\Redis
 *
 * @package cache\adapter
 */
class Redis extends Component implements EngineInterface
{
    /**
     * @var string
     */
    protected $_prefix = 'cache:';

    /**
     * @var \ManaPHP\Redis
     */
    protected $_redis;

    /**
     * Redis constructor.
     *
     * @param string|array $options
     *
     * @throws \ManaPHP\Redis\Exception
     */
    public function __construct($options = [])
    {
        if (is_string($options)) {
            $options = [strpos($options, '://') !== false ? 'redis' : 'prefix' => $options];
        }

        if (isset($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }

        $redis = isset($options['redis']) ? $options['redis'] : 'redis';
        if (strpos($redis, '://') !== false) {
            $this->_redis = new \ManaPHP\Redis($redis);
        } else {
            $this->_redis = Di::getDefault()->getShared($redis);
        }
    }

    /**
     * @param string $key
     *
     * @return string|false
     */
    public function get($key)
    {
        return $this->_redis->get($this->_prefix . $key);
    }

    /**
     * @param string $key
     * @param string $value
     * @param int    $ttl
     *
     * @return void
     */
    public function set($key, $value, $ttl)
    {
        $this->_redis->set($this->_prefix . $key, $value, $ttl);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function delete($key)
    {
        $this->_redis->delete($this->_prefix . $key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return $this->_redis->exists($this->_prefix . $key);
    }
}