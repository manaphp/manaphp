<?php

namespace ManaPHP\Data\Query;

use ArrayAccess;
use JsonSerializable;
use Serializable;

class Row implements ArrayAccess, Serializable, JsonSerializable
{
    /**
     * @var \ManaPHP\Data\Model\SerializeNormalizable
     */
    protected $_model;

    /**
     * @var array
     */
    protected $_row;

    /**
     * @param \ManaPHP\Data\Model\SerializeNormalizable $model
     * @param array                                     $row
     */
    public function __construct($model, $row)
    {
        $this->_model = $model;
        $this->_row = $row;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->_row;
    }

    public function offsetExists($offset)
    {
        return isset($this->_row[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->_row[$offset] ?? null;
    }

    public function offsetSet($offset, $value)
    {
        $this->_row[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->_row[$offset]);
    }

    public function serialize()
    {
        return serialize($this->_row);
    }

    public function unserialize($serialized)
    {
        $this->_row = unserialize($serialized, false);
    }

    public function jsonSerialize()
    {
        return $this->_model->serializeNormalize($this->_row);
    }
}