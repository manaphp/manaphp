<?php

namespace ManaPHP\Security\RateLimiter\Adapter;

use ManaPHP\Security\RateLimiter;

/**
 * Class ManaPHP\Security\RateLimiter\Adapter\Redis
 *
 * @package rateLimiter\adapter
 *
 * @property \Redis                         $rateLimiterRedis
 * @property \ManaPHP\Http\RequestInterface $request
 */
class Redis extends RateLimiter
{
    /**
     * @var string|\ManaPHP\Redis
     */
    protected $_redis;

    /**
     * @var string
     */
    protected $_prefix = 'rate_limiter:';

    /**
     * Redis constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = 'redis')
    {
        if (is_string($options)) {
            $this->_redis = $options;
        } elseif (is_object($options)) {
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
            return $this->_redis = $this->_dependencyInjector->getInstance('ManaPHP\Redis', [$this->_redis]);
        } else {
            return $this->_redis = $this->_dependencyInjector->getShared($this->_redis);
        }
    }

    /**
     * @param string $id
     * @param string $resource
     * @param int    $duration
     * @param int    $times
     *
     * @return bool
     */
    protected function _limit($id, $resource, $duration, $times)
    {
        $key = $this->_prefix . $id . ':' . $resource;
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        $current_times = $redis->incr($key);
        if ($current_times === 1) {
            $redis->setTimeout($key, $duration);
        }

        return $times >= $current_times;
    }
}