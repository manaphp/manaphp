<?php
namespace ManaPHP\Counter\Engine;

use ManaPHP\Component;
use ManaPHP\Counter\EngineInterface;

/**
 * Class ManaPHP\Counter\Engine\Redis
 *
 * @package counter\engine
 *
 * @property \Redis $counterRedis
 */
class Redis extends Component implements EngineInterface
{
    /**
     * @var string|\ManaPHP\Redis
     */
    protected $_redis = 'redis';

    /**
     * @var string
     */
    protected $_prefix = 'counter:';

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
     * @param string $key
     *
     * @return int
     */
    public function get($key)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        return (int)$redis->get($this->_prefix . $key);
    }

    /**
     * @param string $key
     * @param int    $step
     *
     * @return int
     */
    public function increment($key, $step = 1)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        return $redis->incrBy($this->_prefix . $key, $step);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function delete($key)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        $redis->delete($this->_prefix . $key);
    }
}