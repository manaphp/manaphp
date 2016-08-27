<?php
namespace ManaPHP\Store\Engine;

use ManaPHP\Component;
use ManaPHP\Store\EngineInterface;

class Memory extends Component implements EngineInterface
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
    public function get($id)
    {
        return isset($this->_data[$id]) ? $this->_data[$id] : false;
    }

    /**
     * @param array $ids
     *
     * @return array
     */
    public function mGet($ids)
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
    public function set($id, $value)
    {
        $this->_data[$id] = $value;
    }

    /**
     * @param array $idValues
     *
     * @return void
     */
    public function mSet($idValues)
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
    public function delete($id)
    {
        unset($this->_data[$id]);
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function exists($id)
    {
        return isset($this->_data[$id]);
    }
}