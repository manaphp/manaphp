<?php

namespace ManaPHP\Data\Db\SqlFragment;

use ManaPHP\Data\Db\SqlFragmentable;

class Increment implements SqlFragmentable
{
    /**
     * @var string|int|float
     */
    protected $_value;

    /**
     * @var string
     */
    protected $_operator;

    /**
     * @var array
     */
    protected $_bind;

    /**
     * @var string
     */
    protected $_field;

    /**
     * Assignment constructor.
     *
     * @param string|float|int $value
     * @param string           $operator
     * @param array            $bind
     */
    public function __construct($value, $operator = '+', $bind = [])
    {
        $this->_value = $value;
        $this->_operator = $operator;
        $this->_bind = $bind;
    }

    /**
     * @param string $name
     *
     * @return static
     */
    public function setField($name)
    {
        $this->_field = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getSql()
    {
        if ($this->_operator !== null) {
            return "$this->_field = $this->_field $this->_operator :$this->_field";
        } else {
            return $this->_value;
        }
    }

    /**
     * @return array
     */
    public function getBind()
    {
        if ($this->_operator !== null) {
            return [$this->_field => $this->_value];
        } else {
            return $this->_bind;
        }
    }
}