<?php
namespace ManaPHP\Model;

use ManaPHP\Component;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\RuntimeException;

/**
 * Class ManaPHP\Model\Criteria
 *
 * @package ManaPHP\Model
 * @property \ManaPHP\Http\RequestInterface $request
 */
abstract class Criteria extends Component implements CriteriaInterface, \JsonSerializable
{
    /**
     * @var \ManaPHP\Model
     */
    protected $_model;

    /**
     * @var bool
     */
    protected $_multiple;

    /**
     * @var array
     */
    protected $_with = [];

    /**
     * @var string|callable
     */
    protected $_index;

    /**
     * @return \ManaPHP\Model
     */
    public function getModel()
    {
        return $this->_model;
    }

    /**
     * @param string $field
     * @param array  $value
     *
     * @return array
     */
    protected function _normalizeTimeBetween($field, $value)
    {
        $left = $value[0];
        if ($left && is_string($left)) {
            $left = strtotime($left[0] === '-' || $left[0] === '+' ? date('Y-m-d', strtotime($left)) : $left);
        }

        $right = $value[1];
        if ($right && is_string($right)) {
            $right = strtotime($right[0] === '-' || $right[0] === '+' ? date('Y-m-d 23:59:59', strtotime($right)) : $right);
        }

        if (in_array($field, $this->_model->getIntTypeFields(), true)) {
            return [$left ? $left : null, $right ? $right : null];
        } else {
            return [$left ? date('Y-m-d H:i:s', $left) : null, $right ? date('Y-m-d H:i:s', $right) : null];
        }
    }

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
     */
    public function fetch()
    {
        if ($this->_multiple === true) {
            return $this->fetchAll();
        } elseif ($this->_multiple === false) {
            return $this->fetchOne();
        } else {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new RuntimeException('xxx');
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
     */
    protected function _with($instance)
    {
        foreach ($this->_with as $k => $v) {
            $method = 'get' . ucfirst(is_string($k) ? $k : $v);

            if (is_int($k)) {
                $data = $instance->$method()->fetch();
            } else {
                if (is_string($v) || is_array($v)) {
                    $data = $instance->$method()->select($v)->fetch();
                } elseif (is_callable($v)) {
                    $data = $v($instance->$method());
                } else {
                    throw new InvalidValueException(['`:with` with is invalid', 'with' => $k]);
                }
            }

            if ($data instanceof Criteria) {
                $data = $data->fetch();
            }
            $instance->{is_string($k) ? $k : $v} = $data;
        }
    }

    /**
     * @return \ManaPHP\Model|false
     */
    public function fetchOne()
    {
        $modelName = get_class($this->_model);

        if ($r = $this->limit(1)->execute()) {
            $model = new $modelName($r[0]);
            if ($this->_with) {
                $this->_with($model);
            }
            return $model;
        } else {
            return false;
        }
    }

    /**
     * @return \ManaPHP\Model[]
     */
    public function fetchAll()
    {
        $modelName = get_class($this->_model);

        $models = [];
        foreach ($this->execute() as $k => $result) {
            $model = new $modelName($result);
            if ($this->_with) {
                $this->_with($model);
            }

            $models[$k] = $model;
        }

        return $models;
    }
}