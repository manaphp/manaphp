<?php
namespace ManaPHP\Configuration\Settings\Engine;

use ManaPHP\Component;
use ManaPHP\Configuration\Settings\EngineInterface;

class Redis extends Component implements EngineInterface
{
    /**
     * @var string|\ManaPHP\Redis
     */
    protected $_redis = 'redis';

    /**
     * @var string
     */
    protected $_prefix = 'settings:';

    /**
     * Redis constructor.
     *
     * @param string|\ManaPHP\Redis|array $options
     */
    public function __construct($options = 'redis')
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
     * @param string $section
     *
     * @return array
     */
    public function get($section)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        return $redis->hGetAll($this->_prefix . $section);
    }

    /**
     * @param string       $section
     * @param string|array $key
     * @param string       $value
     *
     * @return void
     */
    public function set($section, $key, $value = null)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();

        if (is_array($key)) {
            $redis->hMset($this->_prefix . $section, $key);
        } else {
            $redis->hSet($this->_prefix . $section, $key, $value);
        }
    }

    /**
     * @param string $section
     * @param string $key
     *
     * @return bool
     */
    public function exists($section, $key)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        if ($key === null) {
            return $redis->exists($this->_prefix . $section);
        } else {
            return $redis->hExists($this->_prefix . $section, $key);
        }
    }

    /**
     * @param string $section
     * @param string $key
     *
     * @return void
     */
    public function delete($section, $key)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        if ($key === null) {
            $redis->delete($section);
        } else {
            $redis->hDel($section, $key);
        }
    }
}