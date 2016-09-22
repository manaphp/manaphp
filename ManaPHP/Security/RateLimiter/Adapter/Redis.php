<?php
namespace ManaPHP\Security\RateLimiter\Adapter;

use ManaPHP\Security\RateLimiter;

/**
 * Class Redis
 *
 * @package ManaPHP\Security\RateLimiter\Adapter
 * @property \Redis                         $redis
 * @property \ManaPHP\Http\RequestInterface $request
 */
class Redis extends RateLimiter
{
    /**
     * @var string
     */
    protected $_prefix = 'rate_limiter:';

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
        $current_times = $this->redis->incr($key);
        if ($current_times === 1) {
            $this->redis->setTimeout($key, $duration);
        }

        return $times >= $current_times;
    }
}