<?php
namespace ManaPHP\Task\Metadata\Adapter;

use ManaPHP\Task\Metadata;

/**
 * Class Redis
 *
 * @package ManaPHP\Task\Metadata\Adapter
 * @property \Redis $redis
 */
class Redis extends Metadata
{
    /**
     * Redis constructor.
     *
     * @param string|array $options
     *
     * @throws \ManaPHP\Task\Metadata\Adapter\Exception
     */
    public function __construct($options = [])
    {
        if (!isset($this->redis)) {
            throw new Exception('`redis` service is not registered in `di`.');
        }

        $this->_prefix = 'manaphp:task:';

        parent::__construct($options);
    }

    /**
     * @param string $key
     *
     * @return mixed|false
     */
    public function _get($key)
    {
        return $this->redis->get($key);
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function _set($key, $value)
    {
        $this->redis->set($key, $value);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function _delete($key)
    {
        $this->redis->delete($key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function _exists($key)
    {
        return $this->redis->exists($key);
    }
}