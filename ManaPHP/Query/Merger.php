<?php
namespace ManaPHP\Query;

use ManaPHP\Component;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Model;
use ManaPHP\Query;

/**
 * Class Merger
 * @package ManaPHP\Query
 * @property-read \ManaPHP\Http\RequestInterface $request
 * @property-read \ManaPHP\Paginator             $paginator
 */
class Merger extends Component implements \ManaPHP\QueryInterface, \IteratorAggregate
{
    /**
     * @var \ManaPHP\QueryInterface[]
     */
    protected $queries;

    /**
     * @var int
     */
    protected $_limit;

    /**
     * @var int
     */
    protected $_offset;

    /**
     * @var array
     */
    protected $_order;

    /**
     * Merger constructor.
     *
     * @param array        $queries
     * @param string|array $fields
     */
    public function __construct($queries, $fields = null)
    {
        $this->setQueries($queries);

        if ($fields) {
            $this->select($fields);
        }
    }

    public function __clone()
    {
        foreach ($this->queries as $k => $v) {
            $this->queries[$k] = clone $v;
        }
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->fetch(true));
    }

    public function jsonSerialize()
    {
        return $this->fetch(true);
    }

    public function setDb($db)
    {
        throw new MisuseException(__METHOD__);
    }

    public function from($table, $alias = null)
    {
        throw new MisuseException(__METHOD__);
    }

    public function execute()
    {
        throw new MisuseException(__METHOD__);
    }

    /**
     * @param string[]|\ManaPHP\ModelInterface[]|\ManaPHP\QueryInterface[] $queries
     *
     * @return static
     */
    public function setQueries($queries)
    {
        if (is_string($queries[0])) {
            foreach ($queries as $k => $query) {
                $queries[$k] = new $query();
            }
        }

        foreach ($queries as $query) {
            if ($query instanceof Query) {
                $this->queries[] = $query;
            } elseif ($query instanceof Model) {
                $this->queries[] = $query::query(null, $query);
            }
        }

        return $this;
    }

    /**
     * @return \ManaPHP\QueryInterface[]
     */
    public function getQueries()
    {
        return $this->queries;
    }

    /**
     * @return \ManaPHP\Model
     */
    public function getModel()
    {
        return $this->queries[0]->getModel();
    }

    public function setModel($model)
    {
        return $this;
    }

    /**
     * @param string|array $fields
     *
     * @return static
     */
    public function select($fields)
    {
        foreach ($this->queries as $query) {
            $query->select($fields);
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
        foreach ($this->queries as $query) {
            $query->distinct($distinct);
        }

        return $this;
    }

    /**
     * @param array $expr
     */
    public function aggregate($expr)
    {
        throw new NotSupportedException(__METHOD__);
    }

    /**
     * @param string|array           $filter
     * @param int|float|string|array $value
     *
     * @return static
     */
    public function where($filter, $value = null)
    {
        foreach ($this->queries as $query) {
            $query->where($filter, $value);
        }

        return $this;
    }

    /**
     * @param string $field
     * @param mixed  $value
     *
     * @return static
     */
    public function whereEq($field, $value)
    {
        foreach ($this->queries as $query) {
            $query->whereEq($field, $value);
        }

        return $this;
    }

    /**
     * @param string $field
     * @param string $operator
     * @param mixed  $value
     *
     * @return static
     */
    public function whereCmp($field, $operator, $value)
    {
        foreach ($this->queries as $query) {
            $query->whereCmp($field, $operator, $value);
        }

        return $this;
    }

    /**
     * @param string $field
     * @param int    $divisor
     * @param int    $remainder
     *
     * @return static
     */
    public function whereMod($field, $divisor, $remainder)
    {
        foreach ($this->queries as $query) {
            $query->whereMod($field, $divisor, $remainder);
        }

        return $this;
    }

    /**
     * @param string $expr
     * @param array  $bind
     *
     * @return static
     */
    public function whereExpr($expr, $bind = null)
    {
        foreach ($this->queries as $query) {
            $query->whereExpr($expr, $bind);
        }

        return $this;
    }

    /**
     * @param array $filters
     *
     * @return static
     */
    public function search($filters)
    {
        foreach ($this->queries as $query) {
            $query->search($filters);
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
        foreach ($this->queries as $query) {
            $query->whereBetween($field, $min, $max);
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
        foreach ($this->queries as $query) {
            $query->whereDateBetween($field, $min, $max);
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
        foreach ($this->queries as $query) {
            $query->whereNotBetween($field, $min, $max);
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
        foreach ($this->queries as $query) {
            $query->whereIn($field, $values);
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
        foreach ($this->queries as $query) {
            $query->whereNotIn($field, $values);
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
        foreach ($this->queries as $query) {
            $query->whereInset($field, $value);
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
        foreach ($this->queries as $query) {
            $query->whereNotInset($field, $value);
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
        foreach ($this->queries as $query) {
            $query->whereContains($field, $value);
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
        foreach ($this->queries as $query) {
            $query->whereNotContains($field, $value);
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
        foreach ($this->queries as $query) {
            $query->whereStartsWith($field, $value, $length);
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
        foreach ($this->queries as $query) {
            $query->whereNotStartsWith($field, $value, $length);
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
        foreach ($this->queries as $query) {
            $query->whereEndsWith($field, $value);
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
        foreach ($this->queries as $query) {
            $query->whereNotEndsWith($field, $value);
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
        foreach ($this->queries as $query) {
            $query->whereLike($expr, $value);
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
        foreach ($this->queries as $query) {
            $query->whereNotLike($expr, $value);
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
        foreach ($this->queries as $query) {
            $query->whereRegex($field, $regex, $flags);
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
        foreach ($this->queries as $query) {
            $query->whereNotRegex($field, $regex, $flags);
        }

        return $this;
    }

    /**
     * @param string $expr
     *
     * @return static
     */
    public function whereNull($expr)
    {
        foreach ($this->queries as $query) {
            $query->whereNull($expr);
        }

        return $this;
    }

    /**
     * @param string $expr
     *
     * @return static
     */
    public function whereNotNull($expr)
    {
        foreach ($this->queries as $query) {
            $query->whereNotNull($expr);
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
        foreach ($this->queries as $query) {
            $query->options($options);
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
        foreach ($this->queries as $query) {
            $query->with($with);
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
        if (is_string($orderBy)) {
            $order = [];
            foreach (explode(',', $orderBy) as $item) {
                $item = trim($item);
                if (preg_match('#^([\w\.]+)(\s+asc|\s+desc)?$#i', $item, $match) !== 1) {
                    throw new InvalidValueException(['unknown `:1` order by for `:2` model', $orderBy, get_class($this->getModel())]);
                }
                $order[$match[1]] = (!isset($match[2]) || strtoupper(ltrim($match[2])) === 'ASC') ? SORT_ASC : SORT_DESC;
            }
        } else {
            $order = $orderBy;
        }

        $this->_order = $this->_order ? array_merge($this->_order, $order) : $order;

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
        $this->_limit = $limit > 0 ? (int)$limit : null;
        $this->_offset = $offset > 0 ? (int)$offset : null;

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
            $size = (int)$this->request->get('size', 10);
        }

        if ($page === null) {
            $page = (int)$this->request->get('page', 1);
        }

        $this->limit($size, ($page - 1) * $size);

        return $this;
    }

    /**
     * @param string|array $groupBy
     */
    public function groupBy($groupBy)
    {
        throw new NotSupportedException(__METHOD__);
    }

    /**
     * @param callable|string|array $indexBy
     *
     * @return static
     */
    public function indexBy($indexBy)
    {
        foreach ($this->queries as $query) {
            $query->indexBy($indexBy);
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
        foreach ($this->queries as $query) {
            $query->setFetchType($multiple);
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

        if ($this->_order) {
            foreach ($this->queries as $query) {
                if ($this->_limit) {
                    $t = $query->limit($this->_limit + $this->_offset)->fetch($asArray);
                } else {
                    $t = $query->fetch($asArray);
                }
                $r = $r ? array_merge($r, $t) : $t;
            }

            if (count($this->_order) === 1) {
                $k = key($this->_order);
                $v = current($this->_order);
                if (is_int($k)) {
                    array_multisort(array_column($r, $v), $r);
                } else {
                    array_multisort(array_column($r, $k), $v, $r);
                }
            } else {
                $params = [];
                foreach ($this->_order as $k => $v) {
                    if (is_int($k)) {
                        $params[] = array_column($r, $v);
                    } else {
                        $params[] = array_column($r, $k);
                        $params[] = $v;
                    }
                }
                $params[] = &$r;
                /** @noinspection ArgumentUnpackingCanBeUsedInspection */
                /** @noinspection SpellCheckingInspection */
                call_user_func_array('array_multisort', $params);
            }

            if ($this->_offset) {
                $r = array_slice($r, $this->_offset ?: 0, $this->_limit);
            }
        } elseif ($this->_offset) {
            $count = 0;
            foreach ($this->queries as $query) {
                $c_limit = $this->_limit - count($r);
                $c_offset = max(0, $this->_offset - $count);
                $t = $query->limit($c_limit, $c_offset)->fetch($asArray);
                $r = $r ? array_merge($r, $t) : $t;
                if (count($r) === $this->_limit) {
                    break;
                }
                $count += $t ? count($t) + $c_offset : $query->count();
            }
        } elseif ($this->_limit) {
            foreach ($this->queries as $query) {
                $t = $query->limit($this->_limit - count($r))->fetch($asArray);
                $r = $r ? array_merge($r, $t) : $t;
                if (count($r) >= $this->_limit) {
                    break;
                }
            }
        } else {
            foreach ($this->queries as $query) {
                $t = $query->fetch($asArray);
                $r = $r ? array_merge($r, $t) : $t;
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
        foreach ($this->queries as $query) {
            $t = $query->values($field);
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
        foreach ($this->queries as $query) {
            $t = $query->count($field);
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
        foreach ($this->queries as $query) {
            $t = $query->sum($field);
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
        foreach ($this->queries as $query) {
            $r = $r === null ? $query->max($field) : max($r, $query->max($field));
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
        foreach ($this->queries as $query) {
            $r = $r === null ? $query->min($field) : max($r, $query->min($field));
        }

        return $r;
    }

    /**
     * @param string $field
     *
     * @return float|null
     */
    public function avg($field)
    {
        $count = $this->count($field);
        $sum = $this->count($field);

        return $sum ? $count / $sum : null;
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

        $items = $this->fetch(true);

        if ($this->_limit === null) {
            $count = count($items);
        } elseif (count($items) % $this->_limit === 0) {
            $count = $this->count();
        } else {
            $count = $this->_offset + count($items);
        }

        $paginator = $this->paginator;

        $paginator->items = $items;

        return $paginator->paginate($count, $this->_limit, (int)($this->_offset / $this->_limit) + 1);
    }

    /**
     * @return bool
     */
    public function exists()
    {
        foreach ($this->queries as $query) {
            if ($query->exists()) {
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
        foreach ($this->queries as $query) {
            $t = $query->update($fieldValues);
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
        foreach ($this->queries as $query) {
            $t = $query->delete();
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
        foreach ($this->queries as $query) {
            $query->forceUseMaster($forceUseMaster);
        }

        return $this;
    }

    /**
     * @param string|array $fields
     *
     * @return array|null
     */
    public function first($fields = null)
    {
        foreach ($this->queries as $query) {
            if ($r = $query->select($fields)->limit(1)->fetch(true)) {
                return $r[0];
            }
        }

        return null;
    }

    /**
     * @param string|array $fields
     *
     * @return array
     */
    public function get($fields = null)
    {
        if (!$r = $this->first($fields)) {
            throw new NotFoundException('record is not exists');
        }

        return $r;
    }

    /**
     * @param string|array $fields
     *
     * @return array
     */
    public function all($fields = null)
    {
        return $this->select($fields)->fetch(true);
    }

    /**
     * @param string $field
     * @param mixed  $default
     *
     * @return mixed
     */
    public function value($field, $default = null)
    {
        $r = $this->first([$field]);
        return isset($r[$field]) ? $r[$field] : $default;
    }

    public function when($value, $true_call, $false_call = null)
    {
        foreach ($this->queries as $query) {
            $query->when($value, $true_call, $false_call);
        }

        return $this;
    }

    public function whereDate($field, $date, $format = null)
    {
        foreach ($this->queries as $query) {
            $query->whereDate($field, $date, $format);
        }

        return $this;
    }

    public function whereMonth($field, $date, $format = null)
    {
        foreach ($this->queries as $query) {
            $query->whereMonth($field, $date, $format);
        }

        return $this;
    }

    public function where1v1($id, $value)
    {
        foreach ($this->queries as $query) {
            $query->where1v1($id, $value);
        }

        return $this;
    }
}