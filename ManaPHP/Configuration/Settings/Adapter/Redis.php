<?php
namespace ManaPHP\Configuration\Settings\Adapter;

use ManaPHP\Component;
use ManaPHP\Configuration\SettingsInterface;
use ManaPHP\Exception\InvalidJsonException;
use ManaPHP\Exception\InvalidValueException;

class Redis extends Component implements SettingsInterface
{
    /**
     * @var string|\ManaPHP\Redis
     */
    protected $_redis;

    /**
     * @var string
     */
    protected $_prefix = 'settings:';

    /**
     * @var array
     */
    protected $_cached;

    /**
     * @var int
     */
    protected $_maxDelay = 1;

    /**
     * Settings constructor.
     *
     * @param string|array|\object $options
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

            if (isset($options['maxDelay'])) {
                $this->_maxDelay = $options['maxDelay'];
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
     * @param string $key
     * @param int    $maxDelay
     *
     * @return array
     */
    public function get($key, $maxDelay = null)
    {
        if (isset($this->_cached[$key]) && microtime(true) - $this->_cached[$key][0] > $maxDelay ?: $this->_maxDelay) {
            unset($this->_cached[$key]);
        }

        if (!isset($this->_cached[$key])) {
            $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
            $value = json_decode($redis->get($this->_prefix . $key) ?: '[]', true);
            if (!is_array($value)) {
                throw new InvalidJsonException('the settings of `:key` key value is not json format', ['key' => $key]);
            }
            $this->_cached[$key] = [microtime(true), $value];
        }

        return $this->_cached[$key][1];
    }

    /**
     * @param string|array $key
     * @param array        $value
     *
     * @return static
     */
    public function set($key, $value)
    {
        if (!is_array($value)) {
            throw new InvalidValueException(['the settings of `:key` key value must be array', 'key' => $key]);
        }

        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        $this->_cached[$key] = [microtime(true), $value];
        $redis->set($this->_prefix . $key, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $this;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        return $redis->exists($this->_prefix . $key);
    }

    /**
     * @param string $key
     *
     * @return static
     */
    public function delete($key)
    {
        unset($this->_cached[$key]);
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        $redis->delete($this->_prefix . $key);

        return $this;
    }
}