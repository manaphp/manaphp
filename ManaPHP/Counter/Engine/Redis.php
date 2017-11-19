<?php
namespace ManaPHP\Counter\Engine;

use ManaPHP\Component;
use ManaPHP\Counter\EngineInterface;
use ManaPHP\Di;

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
     * @var string
     */
    protected $_prefix = 'counter:';

    /**
     * @var \ManaPHP\Redis
     */
    protected $_redis;

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

        $this->_redis = Di::getDefault()->getShared(isset($options['redis']) ? $options['redis'] : 'redis');
    }

    /**
     * @param string $key
     *
     * @return int
     */
    public function get($key)
    {
        return (int)$this->_redis->get($this->_prefix . $key);
    }

    /**
     * @param string $key
     * @param int    $step
     *
     * @return int
     */
    public function increment($key, $step = 1)
    {
        return $this->_redis->incrBy($this->_prefix . $key, $step);
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
}