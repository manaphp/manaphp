<?php
namespace ManaPHP\Store\Adapter;

use ManaPHP\Store;

class Memory extends Store
{
    /**
     * @var array
     */
    protected $_data = [];

    /**
     * @param string $id
     *
     * @return string|false
     */
    public function _get($id)
    {
        return isset($this->_data[$id]) ? $this->_data[$id] : false;
    }

    /**
     * @param array $ids
     *
     * @return array
     */
    public function _mGet($ids)
    {
        $idValues = [];
        foreach ($ids as $id) {
            $idValues[$id] = isset($this->_data[$id]) ? $this->_data[$id] : false;
        }

        return $idValues;
    }

    /**
     * @param string $id
     * @param string $value
     *
     * @return void
     */
    public function _set($id, $value)
    {
        $this->_data[$id] = $value;
    }

    /**
     * @param array $idValues
     *
     * @return void
     */
    public function _mSet($idValues)
    {
        foreach ($idValues as $id => $value) {
            $this->_data[$id] = $value;
        }
    }

    /**
     * @param string $id
     *
     * @return void
     */
    public function _delete($id)
    {
        unset($this->_data[$id]);
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function _exists($id)
    {
        return isset($this->_data[$id]);
    }
}