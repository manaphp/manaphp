<?php
namespace ManaPHP\Db;

class Assignment implements AssignmentInterface
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
    protected $_fieldName;

    /**
     * Assignment constructor.
     *
     * @param  string|float|int $value
     * @param  string           $operator
     * @param array             $bind
     */
    public function __construct($value, $operator = null, $bind = [])
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
    public function setFieldName($name)
    {
        $this->_fieldName = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getSql()
    {
        if ($this->_operator !== null) {
            return "$this->_fieldName = $this->_fieldName $this->_operator :$this->_fieldName";
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
            return [$this->_fieldName => $this->_value];
        } else {
            return $this->_bind;
        }
    }
}