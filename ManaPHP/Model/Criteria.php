<?php
namespace ManaPHP\Model;

use ManaPHP\Component;
use ManaPHP\Model\Criteria\Exception as CriteriaException;

/**
 * Class ManaPHP\Model\Criteria
 *
 * @package ManaPHP\Model
 * @property \ManaPHP\Http\RequestInterface $request
 */
abstract class Criteria extends Component implements CriteriaInterface, \JsonSerializable
{
    /**
     * @var string
     */
    protected $_modelName;

    /**
     * @var bool
     */
    protected $_multiple;

    /**
     * @param array $fields
     *
     * @return static
     */
    public function whereRequest($fields)
    {
        foreach ($fields as $k => $v) {
            if (strpos($v, '.') === false) {
                $field = $v;
            } else {
                $parts = explode('.', $v);
                $field = $parts[1];
            }
            $value = $this->request->get(rtrim($field, '=!<>~*^$'));
            if ($value === null) {
                continue;
            } elseif (is_string($value)) {
                $value = trim($value);
                if ($value === '') {
                    continue;
                }
            } elseif (is_array($value)) {
                if (count($value) === 1 && trim($value[0]) === '') {
                    continue;
                }
            }

            $this->where($v, $value);
        }

        return $this;
    }

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
     * @param string $function
     * @param string $alias
     * @param string $field
     *
     * @return mixed
     */
    protected function _groupResult($function, $alias, $field)
    {
        return $this->aggregate([$alias => "$function($field)"])[0][$alias];
    }

    /**
     * @param string $field
     *
     * @return int|float
     */
    public function sum($field)
    {
        return $this->_groupResult('SUM', 'summary', $field);
    }

    /**
     * @param string $field
     *
     * @return int|float
     */
    public function max($field)
    {
        return $this->_groupResult('MAX', 'maximum', $field);
    }

    /**
     * @param string $field
     *
     * @return int|float
     */
    public function min($field)
    {
        return $this->_groupResult('MIN', 'minimum', $field);
    }

    /**
     * @param string $field
     *
     * @return double
     */
    public function avg($field)
    {
        return (double)$this->_groupResult('AVG', 'average', $field);
    }

    /**
     * @param string $field
     *
     * @return int
     */
    public function count($field = null)
    {
        $r = $this->_groupResult('COUNT', 'row_count', $field ?: '*');
        if (is_string($r)) {
            $r = (int)$r;
        }

        return $r;
    }

    public function jsonSerialize()
    {
        return $this->execute();
    }

    /**
     * @return \ManaPHP\Model|false
     */
    public function fetchOne()
    {
        $r = $this->limit(1)->execute();
        return isset($r[0]) ? new $this->_modelName($r[0]) : false;
    }

    /**
     * @return \ManaPHP\Model[]
     */
    public function fetchAll()
    {
        $models = [];
        foreach ($this->execute() as $k => $result) {
            $models[$k] = new $this->_modelName($result);
        }
        return $models;
    }
}