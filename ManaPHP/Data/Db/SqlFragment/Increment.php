<?php

namespace ManaPHP\Data\Db\SqlFragment;

use ManaPHP\Data\Db\SqlFragmentable;

class Increment implements SqlFragmentable
{
    /**
     * @var string|int|float
     */
    protected $value;

    /**
     * @var string
     */
    protected $operator;

    /**
     * @var array
     */
    protected $bind;

    /**
     * @var string
     */
    protected $field;

    /**
     * @param string|float|int $value
     * @param string           $operator
     * @param array            $bind
     */
    public function __construct($value, $operator = '+', $bind = [])
    {
        $this->value = $value;
        $this->operator = $operator;
        $this->bind = $bind;
    }

    /**
     * @param string $name
     *
     * @return static
     */
    public function setField($name)
    {
        $this->field = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getSql()
    {
        if ($this->operator !== null) {
            return "$this->field = $this->field $this->operator :$this->field";
        } else {
            return $this->value;
        }
    }

    /**
     * @return array
     */
    public function getBind()
    {
        if ($this->operator !== null) {
            return [$this->field => $this->value];
        } else {
            return $this->bind;
        }
    }
}