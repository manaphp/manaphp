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
     * @var string
     */
    protected $_prefix;

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
     * @param \ManaPHP\DiInterface $dependencyInjector
     *
     * @return static
     */
    public function setDependencyInjector($dependencyInjector)
    {
        parent::setDependencyInjector($dependencyInjector);

        $this->_dependencyInjector->setAliases('redis', 'rateLimiterRedis');

        if ($this->_prefix === null) {
            $this->_prefix = $this->_dependencyInjector->configure->appID . ':rate_limiter:';
        }

        return $this;
    }

    /**
     * @param string $prefix
     *
     * @return static
     */
    public function setPrefix($prefix)
    {
        $this->_prefix = $prefix;

        return $this;
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
        $current_times = $this->rateLimiterRedis->incr($key);
        if ($current_times === 1) {
            $this->rateLimiterRedis->setTimeout($key, $duration);
        }

        return $times >= $current_times;
    }
}