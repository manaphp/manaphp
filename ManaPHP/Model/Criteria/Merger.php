<?php
namespace ManaPHP\Model\Criteria;

use ManaPHP\Component;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Model;
use ManaPHP\Model\Criteria;

/**
 * Class Merger
 * @package ManaPHP\Model\Criteria
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
class Merger extends Component
{
    /**
     * @var \ManaPHP\Model\CriteriaInterface[]
     */
    protected $_criterias;

    /**
     * @var int
     */
    protected $_limit;

    /**
     * @var int
     */
    public $_offset;

    /**
     * Merger constructor.
     *
     * @param array        $criterias
     * @param string|array $fields
     */
    public function __construct($criterias, $fields = null)
    {
        $this->setCriterias($criterias);

        if ($fields) {
            $this->select($fields);
        }
    }

    /**
     * @param string[]|\ManaPHP\ModelInterface[]|\ManaPHP\Model\CriteriaInterface[] $criterias
     *
     * @return static
     */
    public function setCriterias($criterias)
    {
        if (is_string($criterias[0])) {
            foreach ($criterias as $k => $criteria) {
                $criterias[$k] = new $criteria();
            }
        }

        foreach ($criterias as $criteria) {
            if ($criteria instanceof Criteria) {
                $this->_criterias[] = $criteria;
            } elseif ($criteria instanceof Model) {
                $this->_criterias[] = $criteria::criteria(null, $criteria);
            }
        }

        return $this;
    }

    /**
     * @return \ManaPHP\Model\CriteriaInterface[]
     */
    public function getCriterias()
    {
        return $this->_criterias;
    }

    /**
     * @return \ManaPHP\Model
     */
    public function getModel()
    {
        return $this->_criterias[0]->getModel();
    }

    /**
     * @param string|array $fields
     *
     * @return static
     */
    public function select($fields)
    {
        foreach ($this->_criterias as $criteria) {
            $criteria->select($fields);
        }

        return $this;
    }

    /**
     * Sets SELECT DISTINCT / SELECT ALL flag
     *
     * @param bool $distinct
     *
     * @return static
     */
    public function distinct($distinct = true)
    {
        foreach ($this->_criterias as $criteria) {
            $criteria->distinct($distinct);
        }

        return $this;
    }

    /**
     * @param array $expr
     *
     * @return array
     */
    public function aggregate($expr)
    {
        throw new NotSupportedException('');
    }

    /**
     *
     * @param string|array           $filter
     * @param int|float|string|array $value
     *
     * @return static
     */
    public function where($filter, $value = null)
    {
        foreach ($this->_criterias as $criteria) {
            $criteria->where($filter, $value);
        }

        return $this;
    }

    /**
     * @param array $filters
     *
     * @return static
     */
    public function whereSearch($filters)
    {
        foreach ($this->_criterias as $criteria) {
            $criteria->whereSearch($filters);
        }

        return $this;
    }

    /**
     * @param string           $field
     * @param int|float|string $min
     * @param int|float|string $max
     *
     * @return static
     */
    public function whereBetween($field, $min, $max)
    {
        foreach ($this->_criterias as $criteria) {
            $criteria->whereBetween($field, $min, $max);
        }

        return $this;
    }

    /**
     * @param string     $field
     * @param int|string $min
     * @param int|string $max
     *
     * @return static
     */
    public function whereDateBetween($field, $min, $max)
    {
        foreach ($this->_criterias as $criteria) {
            $criteria->whereDateBetween($field, $min, $max);
        }

        return $this;
    }

    /**
     *
     * @param string           $field
     * @param int|float|string $min
     * @param int|float|string $max
     *
     * @return static
     */
    public function whereNotBetween($field, $min, $max)
    {
        foreach ($this->_criterias as $criteria) {
            $criteria->whereNotBetween($field, $min, $max);
        }

        return $this;
    }

    /**
     * @param string $field
     * @param array  $values
     *
     * @return static
     */
    public function whereIn($field, $values)
    {
        foreach ($this->_criterias as $criteria) {
            $criteria->whereIn($field, $values);
        }

        return $this;
    }

    /**   * @param string $field
     * @param array $values
     *
     * @return static
     */
    public function whereNotIn($field, $values)
    {
        foreach ($this->_criterias as $criteria) {
            $criteria->whereNotIn($field, $values);
        }

        return $this;
    }

    /**
     * @param string $field
     * @param string $value
     *
     * @return static
     */
    public function whereInset($field, $value)
    {
        foreach ($this->_criterias as $criteria) {
            $criteria->whereInset($field, $value);
        }

        return $this;
    }

    /**
     * @param string $field
     * @param string $value
     *
     * @return static
     */
    public function whereNotInset($field, $value)
    {
        foreach ($this->_criterias as $criteria) {
            $criteria->whereNotInset($field, $value);
        }

        return $this;
    }

    /**
     * @param string|array $field
     * @param string       $value
     *
     * @return static
     */
    public function whereContains($field, $value)
    {
        foreach ($this->_criterias as $criteria) {
            $criteria->whereContains($field, $value);
        }

        return $this;
    }

    /**
     * @param string|array $field
     * @param string       $value
     *
     * @return static
     */
    public function whereNotContains($field, $value)
    {
        foreach ($this->_criterias as $criteria) {
            $criteria->whereNotContains($field, $value);
        }

        return $this;
    }

    /**
     * @param string|array $field
     * @param string       $value
     * @param int          $length
     *
     * @return static
     */
    public function whereStartsWith($field, $value, $length = null)
    {
        foreach ($this->_criterias as $criteria) {
            $criteria->whereStartsWith($field, $value, $length);
        }

        return $this;
    }

    /**
     * @param string|array $field
     * @param string       $value
     * @param int          $length
     *
     * @return static
     */
    public function whereNotStartsWith($field, $value, $length = null)
    {
        foreach ($this->_criterias as $criteria) {
            $criteria->whereNotStartsWith($field, $value, $length);
        }

        return $this;
    }

    /**
     * @param string|array $field
     * @param string       $value
     *
     * @return static
     */
    public function whereEndsWith($field, $value)
    {
        foreach ($this->_criterias as $criteria) {
            $criteria->whereEndsWith($field, $value);
        }

        return $this;
    }

    /**
     * @param string|array $field
     * @param string       $value
     *
     * @return static
     */
    public function whereNotEndsWith($field, $value)
    {
        foreach ($this->_criterias as $criteria) {
            $criteria->whereNotEndsWith($field, $value);
        }

        return $this;
    }

    /**
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     */
    public function whereLike($expr, $value)
    {
        foreach ($this->_criterias as $criteria) {
            $criteria->whereLike($expr, $value);
        }

        return $this;
    }

    /**
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     */
    public function whereNotLike($expr, $value)
    {
        foreach ($this->_criterias as $criteria) {
            $criteria->whereNotLike($expr, $value);
        }

        return $this;
    }

    /**
     * @param string $field
     * @param string $regex
     * @param string $flags
     *
     * @return static
     */
    public function whereRegex($field, $regex, $flags = '')
    {
        foreach ($this->_criterias as $criteria) {
            $criteria->whereRegex($field, $regex, $flags);
        }

        return $this;
    }

    /**
     * @param string $field
     * @param string $regex
     * @param string $flags
     *
     * @return static
     */
    public function whereNotRegex($field, $regex, $flags = '')
    {
        foreach ($this->_criterias as $criteria) {
            $criteria->whereNotRegex($field, $regex, $flags);
        }

        return $this;
    }

    /**
     * @param array $options
     *
     * @return static
     */
    public function options($options)
    {
        foreach ($this->_criterias as $criteria) {
            $criteria->options($options);
        }

        return $this;
    }

    /**
     * @param string|array $with
     *
     * @return static
     */
    public function with($with)
    {
        foreach ($this->_criterias as $criteria) {
            $criteria->with($with);
        }

        return $this;
    }

    /**
     * @param string|array $orderBy
     *
     * @return static
     */
    public function orderBy($orderBy)
    {
        foreach ($this->_criterias as $criteria) {
            $criteria->orderBy($orderBy);
        }

        return $this;
    }

    /**
     * @param int $limit
     * @param int $offset
     *
     * @return static
     */
    public function limit($limit, $offset = null)
    {
        $this->_limit = $limit;
        $this->_offset = $offset;

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
     * * @param string|array $groupBy
     *
     * @return static
     */
    public function groupBy($groupBy)
    {
        throw new NotSupportedException('');
    }

    /**
     * @param callable|string|array $indexBy
     *
     * @return static
     */
    public function indexBy($indexBy)
    {
        foreach ($this->_criterias as $criteria) {
            $criteria->indexBy($indexBy);
        }

        return $this;
    }

    /**
     * @param bool $multiple
     *
     * @return static
     */
    public function setFetchType($multiple)
    {
        foreach ($this->_criterias as $criteria) {
            $criteria->setFetchType($multiple);
        }

        return $this;
    }

    /**
     * @param bool $asArray
     *
     * @return \ManaPHP\Model[]|\ManaPHP\Model|array
     */
    public function fetch($asArray = false)
    {
        $r = [];

        if ($this->_offset) {
            $count = 0;
            foreach ($this->_criterias as $criteria) {
                $c_limit = $this->_limit - count($r);
                $c_offset = max(0, $this->_offset - $count);
                $copy = clone $criteria;
                $t = $criteria->limit($c_limit, $c_offset)->fetch($asArray);
                $r = $r ? array_merge($r, $t) : $t;
                if (count($r) === $this->_limit) {
                    break;
                }
                $count += $t ? count($t) + $c_offset : $copy->count();
            }
        } else {
            if ($this->_limit) {
                foreach ($this->_criterias as $criteria) {
                    $t = $criteria->limit($this->_limit - count($r))->fetch($asArray);
                    $r = $r ? array_merge($r, $t) : $t;
                    if (count($r) >= $this->_limit) {
                        break;
                    }
                }
            } else {
                foreach ($this->_criterias as $criteria) {
                    $t = $criteria->fetch($asArray);
                    $r = $r ? array_merge($r, $t) : $t;
                }
            }
        }

        return $r;
    }

    /**
     * @param string $field
     *
     * @return array
     */
    public function values($field)
    {
        $values = [];
        foreach ($this->_criterias as $criteria) {
            $t = $criteria->values($field);
            /** @noinspection SlowArrayOperationsInLoopInspection */
            $values = array_merge($values, $t);
        }

        return array_values(array_unique($values, SORT_REGULAR));
    }

    /**
     * @param string $field
     *
     * @return int
     */
    public function count($field = '*')
    {
        $r = 0;
        foreach ($this->_criterias as $criteria) {
            $t = $criteria->count($field);
            $r += $t;
        }

        return $r;
    }

    /**
     *
     * @param string $field
     *
     * @return int|float|null
     */
    public function sum($field)
    {
        $r = 0;
        foreach ($this->_criterias as $criteria) {
            $t = $criteria->sum($field);
            $r += $t;
        }

        return $r;
    }

    /**
     * @param string $field
     *
     * @return int|float|null
     */
    public function max($field)
    {
        $r = null;
        foreach ($this->_criterias as $criteria) {
            $r = $r === null ? $criteria->max($field) : max($r, $criteria->max($field));
        }

        return $r;
    }

    /**
     * @param string $field
     *
     * @return int|float|null
     */
    public function min($field)
    {
        $r = null;
        foreach ($this->_criterias as $criteria) {
            $r = $r === null ? $criteria->min($field) : max($r, $criteria->min($field));
        }

        return $r;
    }

    /**
     * @param string $field
     *
     * @return double|null
     */
    public function avg($field)
    {
        $count = $this->count($field);
        $sum = $this->count($field);

        return $sum ? $count / $sum : null;
    }

    /**
     * @return int
     */
    protected function _getTotalRows()
    {
        return 0;
    }

    /**
     * @param int $size
     * @param int $page
     *
     * @return \ManaPHP\Paginator
     */
    public function paginate($size = null, $page = null)
    {
        $this->page($size, $page);

        $copy = clone $this;
        $items = $this->fetch();

        if ($this->_limit === null) {
            $count = count($items);
        } else {
            if (count($items) % $this->_limit === 0) {
                $count = $copy->_getTotalRows();
            } else {
                $count = $this->_offset + count($items);
            }
        }

        $paginator = $this->paginator;

        $paginator->items = $items;

        if ($this->_with) {
            $paginator->items = $this->relationsManager->earlyLoad($this->_model, $paginator->items, $this->_with);
        }

        return $paginator->paginate($count, $this->_limit, (int)($this->_offset / $this->_limit) + 1);
    }

    /**
     * @return bool
     */
    public function exists()
    {
        foreach ($this->_criterias as $criteria) {
            if ($criteria->exists()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array $fieldValues
     *
     * @return int
     */
    public function update($fieldValues)
    {
        $r = 0;
        foreach ($this->_criterias as $criteria) {
            $t = $criteria->update($fieldValues);
            $r += $t;
        }

        return $r;
    }

    /**
     * @return int
     */
    public function delete()
    {
        $r = 0;
        foreach ($this->_criterias as $criteria) {
            $t = $criteria->delete();
            $r += $t;
        }

        return $r;
    }

    /**
     * @param bool $forceUseMaster
     *
     * @return static
     */
    public function forceUseMaster($forceUseMaster = true)
    {
        foreach ($this->_criterias as $criteria) {
            $criteria->forceUseMaster($forceUseMaster);
        }

        return $this;
    }
}