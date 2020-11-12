<?php

namespace ManaPHP\Configuration\Settings\Adapter;

use ManaPHP\Component;
use ManaPHP\Configuration\SettingsInterface;
use ManaPHP\Exception\InvalidArgumentException;

class Redis extends Component implements SettingsInterface
{
    /**
     * @var string
     */
    protected $_key = 'settings';

    /**
     * Settings constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['key'])) {
            $this->_key = $options['key'];
        }
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function get($key)
    {
        static $last_time;
        static $cached;

        $time = time();

        if ($last_time !== $time) {
            $last_time = $time;
            $cached = [];
        }

        if (($value = $cached[$key] ?? null) === null) {
            if (($value = $this->redisDb->hGet($this->_key, $key)) === false) {
                throw new InvalidArgumentException(['`%s` key is not exists', $key]);
            }
            $cached[$key] = $value;
        }

        return $value;
    }

    /**
     * @param array $keys
     *
     * @return array
     */
    public function mGet($keys)
    {
        $values = $this->redisDb->hMGet($this->_key, $keys);

        foreach ($keys as $key) {
            if (!isset($values[$key])) {
                throw new InvalidArgumentException(['`%s` key is not exists', $key]);
            }
        }

        return $values;
    }

    /**
     * @param string           $key
     * @param string|int|float $value
     *
     * @return static
     */
    public function set($key, $value)
    {
        $this->redisDb->hSet($this->_key, $key, (string)$value);

        return $this;
    }

    /**
     * @param array $kvs
     *
     * @return static
     */
    public function mSet($kvs)
    {
        $this->redisDb->hMSet($this->_key, $kvs);

        return $this;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return $this->redisDb->hExists($this->_key, $key);
    }

    /**
     * @param string $key
     *
     * @return static
     */
    public function delete($key)
    {
        $this->redisDb->hDel($this->_key, $key);

        return $this;
    }
}
