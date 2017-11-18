<?php

namespace ManaPHP\Store\Engine;

use ManaPHP\Component;
use ManaPHP\Di;
use ManaPHP\Store\EngineInterface;

/**
 * Class ManaPHP\Store\Engine\Redis
 *
 * @package store\engine
 *
 */
class Redis extends Component implements EngineInterface
{
    /**
     * @var string
     */
    protected $_key = 'store:';

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
            $options = [strpos($options, '://') !== false ? 'redis' : 'key' => $options];
        }

        if (isset($options['key'])) {
            $this->_key .= $options['key'];
        }

        if (isset($options['key'])) {
            $this->_key = $options['key'];
        }

        $redis = isset($options['redis']) ? $options['redis'] : 'redis';
        if (strpos($redis, '://') !== false) {
            $this->_redis = new \ManaPHP\Redis($redis);
        } else {
            $this->_redis = Di::getDefault()->getShared($redis);
        }
    }

    /**
     * Fetch content
     *
     * @param string $id
     *
     * @return string|false
     * @throws \ManaPHP\Store\Engine\Exception
     */
    public function get($id)
    {
        return $this->_redis->hGet($this->_key, $id);
    }

    /**
     * Caches content
     *
     * @param string $id
     * @param string $value
     *
     * @return void
     * @throws \ManaPHP\Store\Engine\Exception
     */
    public function set($id, $value)
    {
        $this->_redis->hSet($this->_key, $id, $value);
    }

    /**
     * Delete content
     *
     * @param string $id
     *
     * @void
     * @throws \ManaPHP\Store\Engine\Exception
     */
    public function delete($id)
    {
        $this->_redis->hDel($this->_key, $id);
    }

    /**
     * Check if id exists
     *
     * @param string $id
     *
     * @return bool
     * @throws \ManaPHP\Store\Engine\Exception
     */
    public function exists($id)
    {
        return $this->_redis->hExists($this->_key, $id);
    }
}