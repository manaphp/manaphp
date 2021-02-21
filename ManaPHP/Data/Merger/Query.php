<?php

namespace ManaPHP\Data\Merger;

use ManaPHP\Data\Model;
use ManaPHP\Data\QueryInterface;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Helper\Arr;
use ManaPHP\Helper\Reflection;

/**
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
class Query extends \ManaPHP\Data\Query
{
    /**
     * @var \ManaPHP\Data\QueryInterface[]
     */
    protected $queries;

    /**
     * @param array        $queries
     * @param string|array $fields
     */
    public function __construct($queries, $fields = null)
    {
        $this->setQueries($queries)->select($fields);
    }

    public function __clone()
    {
        foreach ($this->queries as $k => $v) {
            $this->queries[$k] = clone $v;
        }
    }

    /**
     * @param string[]|\ManaPHP\Data\ModelInterface[]|\ManaPHP\Data\QueryInterface[] $queries
     *
     * @return static
     */
    public function setQueries($queries)
    {
        foreach ($queries as $id => $query) {
            if (is_string($query)) {
                $query = $this->getNew($query);
            }

            if (Reflection::isInstanceOf($query, QueryInterface::class)) {
                $this->queries[$id] = $query;
            } elseif ($query instanceof Model) {
                $this->queries[$id] = $query::query();
            } else {
                throw new MisuseException('');
            }
        }

        return $this;
    }

    /**
     * @return \ManaPHP\Data\QueryInterface[]
     */
    public function getQueries()
    {
        return $this->queries;
    }

    public function setDb($db)
    {
        throw new NotSupportedException(__METHOD__);
    }

    public function shard($strategy = null)
    {
        throw new NotSupportedException(__METHOD__);
    }

    public function from($table, $alias = null)
    {
        throw new NotSupportedException(__METHOD__);
    }

    public function getShards()
    {
        throw new NotSupportedException(__METHOD__);
    }

    public function getUniqueShard()
    {
        throw new NotSupportedException(__METHOD__);
    }

    /**
     * @param \ManaPHP\Data\Model $model
     *
     * @return static
     */
    public function setModel($model)
    {
        $this->model = $model;

        foreach ($this->queries as $query) {
            $query->setModel($model);
        }

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
     * @param string|array $fields
     * @param string       $value
     *
     * @return static
     */
    public function whereContains($fields, $value)
    {
        foreach ($this->queries as $query) {
            $query->whereContains($fields, $value);
        }

        return $this;
    }

    /**
     * @param string|array $fields
     * @param string       $value
     *
     * @return static
     */
    public function whereNotContains($fields, $value)
    {
        foreach ($this->queries as $query) {
            $query->whereNotContains($fields, $value);
        }

        return $this;
    }

    /**
     * @param string|array $fields
     * @param string       $value
     * @param int          $length
     *
     * @return static
     */
    public function whereStartsWith($fields, $value, $length = null)
    {
        foreach ($this->queries as $query) {
            $query->whereStartsWith($fields, $value, $length);
        }

        return $this;
    }

    /**
     * @param string|array $fields
     * @param string       $value
     * @param int          $length
     *
     * @return static
     */
    public function whereNotStartsWith($fields, $value, $length = null)
    {
        foreach ($this->queries as $query) {
            $query->whereNotStartsWith($fields, $value, $length);
        }

        return $this;
    }

    /**
     * @param string|array $fields
     * @param string       $value
     *
     * @return static
     */
    public function whereEndsWith($fields, $value)
    {
        foreach ($this->queries as $query) {
            $query->whereEndsWith($fields, $value);
        }

        return $this;
    }

    /**
     * @param string|array $fields
     * @param string       $value
     *
     * @return static
     */
    public function whereNotEndsWith($fields, $value)
    {
        foreach ($this->queries as $query) {
            $query->whereNotEndsWith($fields, $value);
        }

        return $this;
    }

    /**
     * @param string|array $fields
     * @param string       $value
     *
     * @return static
     */
    public function whereLike($fields, $value)
    {
        foreach ($this->queries as $query) {
            $query->whereLike($fields, $value);
        }

        return $this;
    }

    /**
     * @param string|array $fields
     * @param string       $value
     *
     * @return static
     */
    public function whereNotLike($fields, $value)
    {
        foreach ($this->queries as $query) {
            $query->whereNotLike($fields, $value);
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
     * @param string $field
     *
     * @return static
     */
    public function whereNull($field)
    {
        foreach ($this->queries as $query) {
            $query->whereNull($field);
        }

        return $this;
    }

    /**
     * @param string $field
     *
     * @return static
     */
    public function whereNotNull($field)
    {
        foreach ($this->queries as $query) {
            $query->whereNotNull($field);
        }

        return $this;
    }

    /**
     * @param string $id
     * @param string $value
     *
     * @return static
     */
    public function where1v1($id, $value)
    {
        foreach ($this->queries as $query) {
            $query->where1v1($id, $value);
        }

        return $this;
    }

    /**
     * @param string $filter
     * @param array  $bind
     *
     * @return static
     */
    public function whereRaw($filter, $bind = null)
    {
        foreach ($this->queries as $query) {
            $query->whereRaw($filter, $bind);
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
        parent::orderBy($orderBy);

        foreach ($this->queries as $query) {
            $query->orderBy($this->order);
        }

        return $this;
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
     * @param string|array $having
     * @param array        $bind
     *
     * @return static
     */
    public function having($having, $bind = [])
    {
        foreach ($this->queries as $query) {
            $query->having($having, $bind);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function execute()
    {
        $result = [];

        if ($this->order) {
            if ($this->limit) {
                foreach ($this->queries as $query) {
                    $query->limit($this->offset + $this->limit, 0);
                }
            }

            $valid_times = 0;
            foreach ($this->queries as $query) {
                if ($r = $query->execute()) {
                    $valid_times++;
                    $result = $result ? array_merge($result, $r) : $r;
                }
            }

            if ($valid_times > 1) {
                $result = Arr::sort($result, $this->order);
            }

            $result = $this->limit ? array_slice($result, (int)$this->offset, $this->limit) : $result;
        } elseif ($this->limit) {
            foreach ($this->queries as $query) {
                if ($r = $query->execute()) {
                    $result = $result ? array_merge($result, $r) : $r;
                    if (count($result) >= $this->offset + $this->limit) {
                        $result = array_slice($result, (int)$this->offset, $this->limit);
                        return $this->index ? Arr::indexby($result, $this->index) : $result;
                    }
                }
            }

            $result = $result ? array_slice($result, (int)$this->offset, $this->limit) : [];
        } else {
            foreach ($this->queries as $query) {
                if ($r = $query->execute()) {
                    $result = $result ? array_merge($result, $r) : $r;
                }
            }
        }

        return $this->index ? Arr::indexby($result, $this->index) : $result;
    }

    /**
     * @param array $expr
     */
    public function aggregate($expr)
    {
        throw new NotSupportedException(__METHOD__);
    }

    /**
     * @param string $field
     *
     * @return array
     */
    public function values($field)
    {
        $values = [];
        $valid_times = 0;
        foreach ($this->queries as $query) {
            if ($t = $query->values($field)) {
                $valid_times++;
                $values = $values ? array_merge($values, $t) : $t;
            }
        }

        if ($valid_times > 1) {
            $values = array_values(array_unique($values));
            if ($this->order) {
                if (current($this->order) === SORT_ASC) {
                    sort($values);
                } else {
                    rsort($values);
                }
            }
        }

        return $values;
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
            $r += $query->update($fieldValues);
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
            $r += $query->delete();
        }

        return $r;
    }

    /**
     * @param string $table
     * @param string $condition
     * @param string $alias
     * @param string $type
     *
     * @return static
     */
    public function join($table, $condition = null, $alias = null, $type = null)
    {
        foreach ($this->queries as $query) {
            $query->join($table, $condition, $alias, $type);
        }

        return $this;
    }

    public function getSql()
    {
        throw new NotSupportedException(__METHOD__);
    }
}