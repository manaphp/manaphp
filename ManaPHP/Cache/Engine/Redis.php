<?php

namespace ManaPHP\Cache\Engine;

use ManaPHP\Cache\EngineInterface;
use ManaPHP\Component;

/**
 * Class ManaPHP\Cache\Adapter\Redis
 *
 * @package cache\adapter
 *
 * @property \Redis $cacheRedis
 */
class Redis extends Component implements EngineInterface
{
    /**
     * @var string
     */
    protected $_prefix = 'cache:';

    /**
     * Redis constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = [])
    {
        if (is_string($options)) {
            $options = ['prefix' => $options];
        }

        if (isset($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }
    }

    /**
     * @param string $key
     *
     * @return string|false
     */
    public function get($key)
    {
        return $this->cacheRedis->get($this->_prefix . $key);
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
        $this->cacheRedis->set($this->_prefix . $key, $value, $ttl);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function delete($key)
    {
        $this->cacheRedis->delete($this->_prefix . $key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return $this->cacheRedis->exists($this->_prefix . $key);
    }
}