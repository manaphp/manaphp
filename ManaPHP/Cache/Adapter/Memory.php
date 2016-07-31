<?php
namespace ManaPHP\Cache\Adapter;

use ManaPHP\Cache;

class Memory extends Cache
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
    public function _get($key)
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
    public function _set($key, $value, $ttl)
    {
        $this->_data[$key] = ['deadline' => time() + $ttl, 'data' => $value];
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function _delete($key)
    {
        unset($this->_data[$key]);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function _exists($key)
    {
        return isset($this->_data[$key]) && $this->_data[$key]['deadline'] >= time();
    }
}