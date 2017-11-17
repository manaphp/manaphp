<?php

namespace ManaPHP\Store\Adapter;

use ManaPHP\Component;
use ManaPHP\Store\AdapterInterface;

/**
 * Class ManaPHP\Store\Adapter\Redis
 *
 * @package store\adapter
 *
 * @property \Redis               $storeRedis
 * @property \ManaPHP\DiInterface $redisDi
 */
class Redis extends Component implements AdapterInterface
{
    /**
     * @var string
     */
    protected $_key;

    /**
     * Redis constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = [])
    {
        if (is_string($options)) {
            $options = ['key' => $options];
        }

        if (isset($options['key'])) {
            $this->_key .= $options['key'];
        }
    }

    /**
     * @param \ManaPHP\DiInterface $dependencyInjector
     *
     * @return static
     */
    public function setDependencyInjector($dependencyInjector)
    {
        parent::setDependencyInjector($dependencyInjector);

        $this->_dependencyInjector->setAliases('redis', 'storeRedis');
        if ($this->_key === null) {
            $this->_key = $this->_dependencyInjector->configure->appID . ':store:';
        }

        return $this;
    }

    /**
     * @param string $key
     *
     * @return static
     */
    public function setKey($key)
    {
        $this->_key = $key;

        return $this;
    }

    /**
     * Fetch content
     *
     * @param string $id
     *
     * @return string|false
     * @throws \ManaPHP\Store\Adapter\Exception
     */
    public function get($id)
    {
        return $this->storeRedis->hGet($this->_key, $id);
    }

    /**
     * Caches content
     *
     * @param string $id
     * @param string $value
     *
     * @return void
     * @throws \ManaPHP\Store\Adapter\Exception
     */
    public function set($id, $value)
    {
        $this->storeRedis->hSet($this->_key, $id, $value);
    }

    /**
     * Delete content
     *
     * @param string $id
     *
     * @void
     * @throws \ManaPHP\Store\Adapter\Exception
     */
    public function delete($id)
    {
        $this->storeRedis->hDel($this->_key, $id);
    }

    /**
     * Check if id exists
     *
     * @param string $id
     *
     * @return bool
     * @throws \ManaPHP\Store\Adapter\Exception
     */
    public function exists($id)
    {
        return $this->storeRedis->hExists($this->_key, $id);
    }
}