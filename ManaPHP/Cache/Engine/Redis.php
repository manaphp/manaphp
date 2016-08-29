<?php

namespace ManaPHP\Cache\Engine;

use ManaPHP\Cache\EngineInterface;
use ManaPHP\Component;

/**
 * Class Redis
 *
 * @package ManaPHP\Cache\Engine
 *
 * @property \Redis $redis
 */
class Redis extends Component implements EngineInterface
{
    /**
     * @var string
     */
    protected $_prefix = 'manaphp:cache:';

    /**
     * Redis constructor.
     *
     * @param string|array|\ConfManaPHP\Cache\Engine\Redis $options
     */
    public function __construct($options = [])
    {
        if (is_object($options)) {
            $options = (array)$options;
        } elseif (is_string($options)) {
            $options['prefix'] = $options;
        }

        if (isset($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }
    }

    /**
     * @param string $key
     *
     * @return string|false
     */
    public function get($key)
    {
        return $this->redis->get($this->_prefix . $key);
    }

    /**
     * @param string $key
     * @param string $value
     * @param int    $ttl
     *
     * @return void
     */
    public function set($key, $value, $ttl)
    {
        $this->redis->set($this->_prefix . $key, $value, $ttl);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function delete($key)
    {
        $this->redis->delete($this->_prefix . $key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return $this->redis->exists($this->_prefix . $key);
    }
}