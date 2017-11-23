<?php

namespace ManaPHP\Security\RateLimiter\Engine;

use ManaPHP\Component;
use ManaPHP\Security\RateLimiter\EngineInterface;

/**
 * Class ManaPHP\Security\RateLimiter\Engine\Redis
 *
 * @package rateLimiter\engine
 */
class Redis extends Component implements EngineInterface
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
     * @param string $type
     * @param string $id
     * @param int    $duration
     *
     * @return int
     */
    public function check($type, $id, $duration)
    {
        $key = $this->_prefix . $type . ':' . $id;
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        $current_times = $redis->incr($key);
        if ($current_times === 1) {
            $redis->setTimeout($key, $duration);
        }

        return $current_times;
    }
}