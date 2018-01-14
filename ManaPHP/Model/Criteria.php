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
     * @var array
     */
    protected $_with = [];

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
     * @param string|array $with
     *
     * @return static
     */
    public function with($with)
    {
        if (is_string($with)) {
            $this->_with[] = $with;
        } else {
            $this->_with = array_merge($this->_with, $with);
        }

        return $this;
    }

    /**
     * @param int $size
     * @param int $page
     *
     * @return static
     */
    public function page($size = null, $page = null)
    {
        if ($size === null) {
            $size = $this->request->get('size', 'int', 10);
        }

        if ($page === null) {
            $page = $this->request->get('page', 'int', 1);
        }

        $this->limit($size, ($page - 1) * $size);

        return $this;
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
        $r = $this->aggregate([$alias => "$function($field)"]);
        return isset($r[0]) ? $r[0][$alias] : 0;
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
        return $this->fetch();
    }

    /**
     * @param \ManaPHP\Model $instance
     *
     * @return \ManaPHP\Model
     */
    protected function _with($instance)
    {
        foreach ($this->_with as $k => $v) {
            if (is_int($k)) {
                $method = 'get' . ucfirst($v);
                $instance->assign([$v => $instance->$method()->fetch()], []);
            } else {
                $method = 'get' . ucfirst($k);
                if (is_array($v) || is_string($v)) {
                    $instance->assign([$k => $instance->$method()->select($v)->fetch()], []);
                } elseif (is_callable($v)) {
                    $instance->assign([$k => $v($instance->$method())->fetch()]);
                }
            }
        }

        return $instance;
    }

    /**
     * @return \ManaPHP\Model|false
     */
    public function fetchOne()
    {
        /**
         * @var \ManaPHP\Model $r
         */
        $r = $this->limit(1)->execute();
        $r = isset($r[0]) ? new $this->_modelName($r[0]) : false;
        if ($r && $this->_with) {
            $this->_with($r);
        }

        return $r;
    }

    /**
     * @return \ManaPHP\Model[]
     */
    public function fetchAll()
    {
        $models = [];
        foreach ($this->execute() as $k => $result) {
            $models[$k] = new $this->_modelName($result);
            if ($this->_with) {
                $this->_with($models[$k]);
            }
        }

        return $models;
    }
}