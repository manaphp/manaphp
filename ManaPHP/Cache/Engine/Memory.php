<?php
namespace ManaPHP\Cache\Engine;

use ManaPHP\Cache\EngineInterface;

/**
 * Class ManaPHP\Cache\Adapter\Memory
 *
 * @package cache\adapter
 */
class Memory implements EngineInterface
{
    /**
     * @var array
     */
    protected $_data = [];

    /**
     * @param string $key
     *
     * @return string|false
     */
    public function get($key)
    {
        if (isset($this->_data[$key])) {
            if ($this->_data[$key]['deadline'] >= time()) {
                return $this->_data[$key]['data'];
            } else {
                unset($this->_data[$key]);

                return false;
            }
        } else {
            return false;
        }
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
        $this->_data[$key] = ['deadline' => time() + $ttl, 'data' => $value];
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function delete($key)
    {
        unset($this->_data[$key]);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return isset($this->_data[$key]) && $this->_data[$key]['deadline'] >= time();
    }
}