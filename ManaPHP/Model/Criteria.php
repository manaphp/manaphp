<?php
namespace ManaPHP\Model;

use ManaPHP\Component;
use ManaPHP\Model\Criteria\Exception as CriteriaException;

abstract class Criteria extends Component implements CriteriaInterface, \JsonSerializable
{
    /**
     * @var bool
     */
    protected $_multiple;

    /**
     * alias of whereBetween
     *
     * @param string           $expr
     * @param int|float|string $min
     * @param int|float|string $max
     *
     * @return static
     * @deprecated
     */
    public function betweenWhere($expr, $min, $max)
    {
        return $this->whereBetween($expr, $min, $max);
    }

    /**
     * alias of whereNotBetween
     *
     * @param string           $expr
     * @param int|float|string $min
     * @param int|float|string $max
     *
     * @return static
     * @deprecated
     */
    public function notBetweenWhere($expr, $min, $max)
    {
        return $this->whereNotBetween($expr, $min, $max);
    }

    /**
     * alias of whereIn
     *
     * @param string                           $expr
     * @param array|\ManaPHP\Db\QueryInterface $values
     *
     * @return static
     * @deprecated
     */
    public function inWhere($expr, $values)
    {
        return $this->whereIn($expr, $values);
    }

    /**
     * alias of whereNotIn
     *
     * @param string                           $expr
     * @param array|\ManaPHP\Db\QueryInterface $values
     *
     * @return static
     * @deprecated
     */
    public function notInWhere($expr, $values)
    {
        return $this->whereNotIn($expr, $values);
    }

    /**
     * @param bool $multiple
     *
     * @return static
     */
    public function setFetchType($multiple)
    {
        $this->_multiple = $multiple;

        return $this;
    }

    /**
     * @return \ManaPHP\Model[]|\ManaPHP\Model|false
     * @throws \ManaPHP\Model\Criteria\Exception
     */
    public function fetch()
    {
        if ($this->_multiple === true) {
            return $this->fetchAll();
        } elseif ($this->_multiple === false) {
            return $this->fetchOne();
        } else {
            throw new CriteriaException('xxx');
        }
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->aggregate(['count' => 'COUNT(*)'])[0]['count'];
    }

    public function jsonSerialize()
    {
        return $this->asArray();
    }
}