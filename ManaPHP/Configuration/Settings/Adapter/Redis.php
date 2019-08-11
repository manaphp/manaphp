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
    protected $_redis = 'redis';

    /**
     * @var string
     */
    protected $_prefix = 'settings:';

    /**
     * Settings constructor.
     *
     * @param array $options
     */
    public function __construct($options = null)
    {
        if (isset($options['redis'])) {
            $this->_redis = $options['redis'];
        }

        if (isset($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }
    }

    /**
     * @param string $key
     *
     * @return array
     */
    public function get($key)
    {
        if (is_string($this->_redis)) {
            $this->_redis = $this->_di->getShared($this->_redis);
        }

        $value = json_decode($this->_redis->get($this->_prefix . $key) ?: '[]', true);
        if (!is_array($value)) {
            throw new InvalidJsonException('the settings of `:key` key value is not json format', ['key' => $key]);
        }
        return $value;
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

        if (is_string($this->_redis)) {
            $this->_redis = $this->_di->getShared($this->_redis);
        }

        $this->_redis->set($this->_prefix . $key, json_stringify($value));

        return $this;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        if (is_string($this->_redis)) {
            $this->_redis = $this->_di->getShared($this->_redis);
        }

        return $this->_redis->exists($this->_prefix . $key);
    }

    /**
     * @param string $key
     *
     * @return static
     */
    public function delete($key)
    {
        if (is_string($this->_redis)) {
            $this->_redis = $this->_di->getShared($this->_redis);
        }

        $this->_redis->delete($this->_prefix . $key);

        return $this;
    }
}