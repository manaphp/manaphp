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
     * @var int
     */
    protected $_ttl = 1;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['redisDb'])) {
            $this->_injections['redisDb'] = $options['redisDb'];
        }

        if (isset($options['key'])) {
            $this->_key = $options['key'];
        }

        if (isset($options['ttl'])) {
            $this->_ttl = (int)$options['ttl'];
        }
    }

    /**
     * @param string $key
     * @param string $default
     *
     * @return string
     */
    public function get($key, $default = null)
    {
        if ($this->_ttl <= 0) {
            if (($value = $this->redisDb->hGet($this->_key, $key)) === false) {
                if ($default === null) {
                    throw new InvalidArgumentException(['`%s` key is not exists', $key]);
                } else {
                    $value = $default;
                }
            }
            return $value;
        } else {
            return apcu_remember(
                $this->_key . ':' . $key, $this->_ttl, function () use ($default, $key) {
                if (($value = $this->redisDb->hGet($this->_key, $key)) === false) {
                    if ($default === null) {
                        throw new InvalidArgumentException(['`%s` key is not exists', $key]);
                    } else {
                        $value = $default;
                    }
                }
                return $value;
            }
            );
        }
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
