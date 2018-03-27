<?php

namespace ManaPHP\Store\Engine;

use ManaPHP\Component;
use ManaPHP\Store\EngineInterface;

/**
 * Class ManaPHP\Store\Engine\Redis
 *
 * @package store\engine
 *
 */
class Redis extends Component implements EngineInterface
{
    /**
     * @var \ManaPHP\Redis
     */
    protected $_redis = 'redis';

    /**
     * @var string
     */
    protected $_prefix = 'store:';

    /**
     * Redis constructor.
     *
     * @param string|\ManaPHP\Redis|array $options
     *
     */
    public function __construct($options = [])
    {
        if (is_string($options) || is_object($options)) {
            $this->_redis = $options;
        } else {
            if (isset($options['redis'])) {
                $this->_redis = $options['redis'];
            }

            if (isset($options['prefix'])) {
                $this->_prefix = $options['prefix'];
            }
        }
    }

    /**
     * @return \ManaPHP\Redis
     */
    protected function _getRedis()
    {
        if (strpos($this->_redis, '/') !== false) {
            return $this->_redis = $this->_di->getInstance('ManaPHP\Redis', [$this->_redis]);
        } else {
            return $this->_redis = $this->_di->getShared($this->_redis);
        }
    }

    /**
     * Fetch content
     *
     * @param string $id
     *
     * @return string|false
     */
    public function get($id)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        return $redis->hGet($this->_prefix, $id);
    }

    /**
     * Caches content
     *
     * @param string $id
     * @param string $value
     *
     * @return void
     */
    public function set($id, $value)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        $redis->hSet($this->_prefix, $id, $value);
    }

    /**
     * Delete content
     *
     * @param string $id
     *
     * @void
     */
    public function delete($id)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        $redis->hDel($this->_prefix, $id);
    }

    /**
     * Check if id exists
     *
     * @param string $id
     *
     * @return bool
     */
    public function exists($id)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        return $redis->hExists($this->_prefix, $id);
    }
}