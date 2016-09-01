<?php

namespace ManaPHP\Cache\Adapter;

use ManaPHP\Cache\AdapterInterface;
use ManaPHP\Component;

/**
 * Class Redis
 *
 * @package ManaPHP\Cache\Adapter
 *
 * @property \Redis               $redis
 * @property \ManaPHP\DiInterface $redisDi
 */
class Redis extends Component implements AdapterInterface
{
    /**
     * @var string
     */
    protected $_prefix = 'manaphp:cache:';

    /**
     * @var string
     */
    protected $_service = 'cache';

    /**
     * Redis constructor.
     *
     * @param string|array|\ConfManaPHP\Cache\Adapter\Redis $options
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

    public function setDependencyInjector($dependencyInjector)
    {
        parent::setDependencyInjector($dependencyInjector);
        if (isset($this->redisDi)) {
            $this->redis = $this->redisDi->getShared($this->_service, ['prefix' => $this->_prefix]);
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