<?php
namespace ManaPHP\Cache\Adapter;

use ManaPHP\Cache;

class Memory extends Cache
{
    /**
     * @var array
     */
    protected $_data = [];

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

    public function _set($key, $value, $ttl)
    {
        $this->_data[$key] = ['deadline' => time() + $ttl, 'data' => $value];
    }

    public function _delete($key)
    {
        unset($this->_data[$key]);
    }

    public function _exists($key)
    {
        return isset($this->_data[$key]) && $this->_data[$key]['deadline'] >= time();
    }
}