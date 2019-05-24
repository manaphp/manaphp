<?php
namespace ManaPHP\Db;

use ManaPHP\Di;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Model\Expression\Increment;
use ManaPHP\Model\Expression\Raw;
use ManaPHP\Model\ExpressionInterface;

class Query extends \ManaPHP\Query implements QueryInterface
{
    /**
     * @var array
     */
    protected $_fields;

    /**
     * @var array
     */
    protected $_tables = [];

    /**
     * @var array
     */
    protected $_joins = [];

    /**
     * @var array
     */
    protected $_conditions = [];

    /**
     * @var string
     */
    protected $_group;

    /**
     * @var array
     */
    protected $_having;

    /**
     * @var string
     */
    protected $_order;

    /**
     * @var string|callable
     */
    protected $_index;

    /**
     * @var bool
     */
    protected $_forUpdate;

    /**
     * @var array
     */
    protected $_bind = [];

    /**
     * @var int
     */
    protected $_hiddenParamNumber = 0;

    /**
     * @var array
     */
    protected $_union = [];

    /**
     * @var string
     */
    protected $_sql;

    /**
     * @var \ManaPHP\DbInterface
     */
    protected $_db;

    /**
     * @param \ManaPHP\DbInterface|string $db
     */
    public function __construct($db = null)
    {
        $this->_db = $db;
    }

    /**
     * @param \ManaPHP\DbInterface|string $db
     *
     * @return static
     */
    public function setDb($db)
    {
        $this->_db = $db;

        return $this;
    }

    /**
     * @return \ManaPHP\DbInterface
     */
    public function getConnection()
    {
        if (!$this->_di) {
            $this->_di = Di::getDefault();
        }

        if (is_object($this->_db)) {
            return $this->_db;
        } elseif ($this->_model) {
            return $this->_di->getShared($this->_model->getDb($this->_bind));
        } else {
            return $this->_di->getShared($this->_db ?: 'db');
        }
    }

    /**
     * @return string
     */
    public function getSource()
    {
        if ($this->_model) {
            return $this->_model->getSource($this->_bind);
        } else {
            return current($this->_tables);
        }
    }

    /**
     * @param string|array $fields
     *
     * @return static
     */
    public function select($fields)
    {
        if (!$fields) {
            return $this;
        }

        if (is_string($fields)) {
            $fields = (array)preg_split('#[\s,]+#', $fields, -1, PREG_SPLIT_NO_EMPTY);
        }

        $r = '';
        /** @noinspection ForeachSourceInspection */
        foreach ($fields as $k => $v) {
            if (strpos($v, '[') === false && strpos($v, '(') === false) {
                /** @noinspection NotOptimalIfConditionsInspection */
                if (is_int($k)) {
                    $r .= preg_replace('#\w+#', '[\\0]', $v) . ', ';
                } else {
                    $r .= preg_replace('#\w+#', '[\\0]', $v) . ' AS [' . $k . '], ';
                }
            } elseif (is_int($k)) {
                $r .= $v . ', ';
            } else {
                $r .= $v . ' AS [' . $k . '], ';
            }
        }
        $this->_fields = substr($r, 0, -2);

        return $this;
    }

    /**
     * @param string $table
     * @param string $alias
     *
     * @return static
     */
    public function from($table, $alias = null)
    {
        if ($table) {
            if (!$this->_model && strpos($table, '\\') !== false) {
                $this->_model = $this->_di->getShared($table);
            }

            $this->_tables = $alias ? [$alias => $table] : [$table];

            if ($table instanceof self) {
                $this->_db = $table->_db;
            }
        }

        return $this;
    }

    /**
     * @param string $table
     * @param string $alias
     *
     * @return static
     */
    public function addFrom($table, $alias = null)
    {
        if ($alias) {
            $this->_tables[$alias] = $table;
        } else {
            $this->_tables[] = $table;
        }

        return $this;
    }

    /**
     * @param string|\ManaPHP\Db\QueryInterface $table
     * @param string                            $condition
     * @param string                            $alias
     * @param string                            $type
     *
     * @return static
     */
    public function join($table, $condition = null, $alias = null, $type = null)
    {
        if (strpos($condition, '[') === false && strpos($condition, '(') === false) {
            $condition = (string)preg_replace('#\w+#', '[\\0]', $condition);
        }

        if (is_string($table) && strpos($table, '\\') !== false) {
            /** @var \ManaPHP\Model $model */
            $model = $this->_di->getShared($table);
            $table = $model->getSource($this->_bind);
        }

        $this->_joins[] = [$table, $condition, $alias, $type];

        return $this;
    }

    /**
     * @param string|\ManaPHP\Db\QueryInterface $table
     * @param string                            $condition
     * @param string                            $alias
     *
     * @return static
     */
    public function innerJoin($table, $condition = null, $alias = null)
    {
        return $this->join($table, $condition, $alias, 'INNER');
    }

    /**
     * @param string|\ManaPHP\Db\QueryInterface $table
     * @param string                            $condition
     * @param string                            $alias
     *
     * @return static
     */
    public function leftJoin($table, $condition = null, $alias = null)
    {
        return $this->join($table, $condition, $alias, 'LEFT');
    }

    /**
     * @param string|\ManaPHP\Db\QueryInterface $table
     * @param string                            $condition
     * @param string                            $alias
     *
     * @return static
     */
    public function rightJoin($table, $condition = null, $alias = null)
    {
        return $this->join($table, $condition, $alias, 'RIGHT');
    }

    /**
     *
     * @param string|array           $filters
     * @param int|float|string|array $values
     *
     * @return static
     */
    public function where($filters, $values = null)
    {
        if ($filters === null) {
            return $this;
        }

        foreach (is_array($filters) ? $filters : [$filters => $values] as $filter => $value) {
            if (is_int($filter)) {
                $this->whereExpr($value);
            } elseif (is_array($value)) {
                if (preg_match('#([~@!<>|=%]+)$#', $filter, $match)) {
                    $operator = $match[1];
                    $field = substr($filter, 0, -strlen($operator));
                    if ($operator === '~=') {
                        if (count($value) !== 2) {
                            throw new MisuseException(['`value of :filter` filter is invalid', 'filter' => $filter]);
                        }
                        $this->whereBetween($field, $value[0], $value[1]);
                    } elseif ($operator === '@=') {
                        $this->whereDateBetween($field, $value[0], $value[1]);
                    } elseif ($operator === '|=') {
                        $this->whereIn($field, $value);
                    } elseif ($operator === '!=' || $operator === '<>') {
                        $this->whereNotIn($field, $value);
                    } elseif ($operator === '=') {
                        $this->whereIn($field, $value);
                    } elseif ($operator === '%=') {
                        $this->whereMod($field, $value[0], $value[1]);
                    } else {
                        throw new MisuseException(['unknown `:operator` operator', 'operator' => $operator]);
                    }
                } elseif (!$value || isset($value[0])) {
                    $this->whereIn($filter, $value);
                } else {
                    $this->whereExpr($filter, $value);
                }
            } elseif (preg_match('#^([\w\.]+)([<>=!^$*~,@dm?]*)$#', $filter, $matches) === 1) {
                list(, $field, $operator) = $matches;
                if (strpos($operator, '?') !== false) {
                    $value = is_string($value) ? trim($value) : $value;
                    if ($value === '' || $value === null) {
                        continue;
                    }
                    $operator = substr($operator, 0, -1);
                }

                if ($operator === '') {
                    $operator = '=';
                }

                if (in_array($operator, ['=', '~=', '!=', '<>', '>', '>=', '<', '<='], true)) {
                    $this->whereCmp($field, $operator, $value);
                } elseif ($operator === '^=') {
                    $this->whereStartsWith($field, $value);
                } elseif ($operator === '$=') {
                    $this->whereEndsWith($field, $value);
                } elseif ($operator === '*=') {
                    $this->whereContains($field, $value);
                } elseif ($operator === ',=') {
                    $this->whereInset($field, $value);
                } elseif ($operator === '@d=') {
                    $this->whereDate($field, $value);
                } elseif ($operator === '@m=') {
                    $this->whereMonth($field, $value);
                } else {
                    throw new MisuseException(['unknown `:operator` operator', 'operator' => $operator]);
                }
            } elseif (strpos($filter, ',') !== false && preg_match('#^[\w,\.]+$#', $filter)) {
                $this->where1v1($filter, $value);
            } else {
                $this->whereExpr($filter);
            }
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
        $bind_key = strpos($field, '.') !== false ? strtr($field, '.', '_') : $field;
        $normalizedField = preg_replace('#\w+#', '[\\0]', $field);
        $this->_conditions[] = "$normalizedField=:$bind_key";
        $this->_bind[$bind_key] = $value;

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
        $bind_key = strpos($field, '.') ? strtr($field, '.', '_') : $field;
        $normalizedField = preg_replace('#\w+#', '[\\0]', $field);

        if ($operator === '=') {
            if ($value === null) {
                $this->_conditions[] = $normalizedField . ' IS NULL';
            } else {
                $this->_conditions[] = $normalizedField . '=:' . $bind_key;
                $this->_bind[$bind_key] = $value;
            }
        } elseif ($operator === '~=') {
            if ($value === 0 || $value === 0.0) {
                $this->_conditions[] = "$normalizedField IS NULL OR $normalizedField=0";
            } elseif ($value === '') {
                $this->_conditions[] = "$normalizedField IS NULL OR $normalizedField=''";
            } else {
                $this->_conditions[] = $normalizedField . '=' . $bind_key;
                $this->_bind[$bind_key] = $value;
            }
        } elseif ($operator === '!=' || $operator === '<>') {
            if ($value === null) {
                $this->_conditions[] = $normalizedField . ' IS NOT NULL';
            } else {
                $this->_conditions[] = $normalizedField . $operator . ':' . $bind_key;
                $this->_bind[$bind_key] = $value;
            }
        } elseif (in_array($operator, ['>', '>=', '<', '<='], true)) {
            $this->_conditions[] = $normalizedField . $operator . ':' . $bind_key;
            $this->_bind[$bind_key] = $value;
        } else {
            throw new MisuseException(['unknown `:operator` operator', 'operator' => $operator]);
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
        if (!is_int($divisor)) {
            throw new MisuseException('divisor must be an integer');
        }

        if (!is_int($remainder)) {
            throw new MisuseException('remainder must be an integer');
        }

        $this->_conditions[] = "$field%$divisor=$remainder";

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
        $this->_conditions[] = $expr;

        if (is_array($bind)) {
            $this->_bind = array_merge($this->_bind, $bind);
        }

        return $this;
    }

    /**
     * @param string           $expr
     * @param int|float|string $min
     * @param int|float|string $max
     *
     * @return static
     */
    public function whereBetween($expr, $min, $max)
    {
        if ($min === null || $min === '') {
            return $max === null || $max === '' ? $this : $this->whereCmp($expr, '<=', $max);
        } elseif ($max === null || $max === '') {
            return $min === null || $min === '' ? $this : $this->whereCmp($expr, '>=', $min);
        }

        if (strpos($expr, '[') === false && strpos($expr, '(') === false) {

            if (strpos($expr, '.') !== false) {
                $id = strtr($expr, '.', '_');
                $expr = '[' . str_replace('.', '].[', $expr) . ']';

            } else {
                $id = $expr;
                $expr = '[' . $expr . ']';
            }

            $minKey = $id . '_min';
            $maxKey = $id . '_max';
        } else {
            $minKey = '_min_' . $this->_hiddenParamNumber;
            $maxKey = '_max_' . $this->_hiddenParamNumber;
            $this->_hiddenParamNumber++;
        }

        $this->_conditions[] = "$expr BETWEEN :$minKey AND :$maxKey";

        $this->_bind[$minKey] = $min;
        $this->_bind[$maxKey] = $max;

        return $this;
    }

    /**
     * @param string           $expr
     * @param int|float|string $min
     * @param int|float|string $max
     *
     * @return static
     */
    public function whereNotBetween($expr, $min, $max)
    {
        if ($min === null || $min === '') {
            return $max === null || $max === '' ? $this : $this->whereCmp($expr, '>', $max);
        } elseif ($max === null || $max === '') {
            return $min === null || $min === '' ? $this : $this->whereCmp($expr, '<', $min);
        }

        $minKey = '_min_' . $this->_hiddenParamNumber;
        $maxKey = '_max_' . $this->_hiddenParamNumber;

        $this->_hiddenParamNumber++;

        if (strpos($expr, '[') === false && strpos($expr, '(') === false) {
            if (strpos($expr, '.') !== false) {
                $expr = '[' . str_replace('.', '].[', $expr) . ']';
            } else {
                $expr = '[' . $expr . ']';
            }
        }

        $this->_conditions[] = "$expr NOT BETWEEN :$minKey AND :$maxKey";

        $this->_bind[$minKey] = $min;
        $this->_bind[$maxKey] = $max;

        return $this;
    }

    /**
     * @param string                           $expr
     * @param array|\ManaPHP\Db\QueryInterface $values
     *
     * @return static
     */
    public function whereIn($expr, $values)
    {
        if ($values instanceof $this) {
            $this->_conditions[] = $expr . ' IN (' . $values->getSql() . ')';
            $this->_bind = array_merge($this->_bind, $values->getBind());
        } elseif ($values) {
            if (strpos($expr, '[') === false && strpos($expr, '(') === false) {
                if (strpos($expr, '.') !== false) {
                    $expr = '[' . str_replace('.', '].[', $expr) . ']';
                } else {
                    $expr = '[' . $expr . ']';
                }
            }

            if (is_int(current($values))) {
                $this->_conditions[] = $expr . ' IN (' . implode(', ', array_map('intval', $values)) . ')';
            } else {
                $bindKeys = [];
                /** @noinspection ForeachSourceInspection */
                foreach ($values as $k => $value) {
                    $key = '_in_' . $this->_hiddenParamNumber . '_' . $k;
                    $bindKeys[] = ":$key";
                    $this->_bind[$key] = $value;
                }

                $this->_conditions[] = $expr . ' IN (' . implode(', ', $bindKeys) . ')';
                $this->_hiddenParamNumber++;
            }
        } else {
            $this->_conditions[] = 'FALSE';
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
        $this->_conditions[] = $filter;

        if ($bind !== null) {
            $this->_bind = array_merge($this->_bind, $bind);
        }

        return $this;
    }

    /**
     * @param string                           $expr
     * @param array|\ManaPHP\Db\QueryInterface $values
     *
     * @return static
     */
    public function whereNotIn($expr, $values)
    {
        if ($values instanceof $this) {
            $this->_conditions[] = $expr . ' NOT IN (' . $values->getSql() . ')';
            $this->_bind = array_merge($this->_bind, $values->getBind());
        } elseif ($values) {
            if (strpos($expr, '[') === false && strpos($expr, '(') === false) {
                if (strpos($expr, '.') !== false) {
                    $expr = '[' . str_replace('.', '].[', $expr) . ']';
                } else {
                    $expr = '[' . $expr . ']';
                }
            }

            if (is_int(current($values))) {
                $this->_conditions[] = $expr . ' NOT IN (' . implode(', ', array_map('intval', $values)) . ')';
            } else {
                $bindKeys = [];
                /** @noinspection ForeachSourceInspection */
                foreach ($values as $k => $value) {
                    $key = '_in_' . $this->_hiddenParamNumber . '_' . $k;
                    $bindKeys[] = ':' . $key;
                    $this->_bind[$key] = $value;
                }

                $this->_hiddenParamNumber++;

                $this->_conditions[] = $expr . ' NOT IN (' . implode(', ', $bindKeys) . ')';
            }
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
        $key = strtr($field, '.', '_');
        $this->_conditions[] = 'FIND_IN_SET(:' . $key . ', ' . '[' . str_replace('.', '].[', $field) . '])>0';
        $this->_bind[$key] = $value;

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
        $key = strtr($field, '.', '_');
        $this->_conditions[] = 'FIND_IN_SET(:' . $key . ', ' . '[' . str_replace('.', '].[', $field) . '])=0';
        $this->_bind[$key] = $value;

        return $this;
    }

    /**
     * @param string|array $expr
     * @param string       $like
     *
     * @return static
     */
    public function whereLike($expr, $like)
    {
        if ($like === '') {
            return $this;
        }

        if (is_array($expr)) {
            $conditions = [];
            /** @noinspection ForeachSourceInspection */
            foreach ($expr as $field) {
                $key = strtr($field, '.', '_');
                if (strpos($field, '.') !== false) {
                    $conditions[] = '[' . str_replace('.', '].[', $field) . ']' . ' LIKE :' . $key;
                } else {
                    $conditions[] = '[' . $field . '] LIKE :' . $key;
                }

                $this->_bind[$key] = $like;
            }

            $this->_conditions[] = implode(' OR ', $conditions);
        } else {
            $key = strtr($expr, '.', '_');

            if (strpos($expr, '[') === false && strpos($expr, '(') === false) {
                if (strpos($expr, '.') !== false) {
                    $expr = '[' . str_replace('.', '].[', $expr) . ']';
                } else {
                    $expr = '[' . $expr . ']';
                }
            }

            $this->_conditions[] = $expr . ' LIKE :' . $key;

            $this->_bind[$key] = $like;
        }

        return $this;
    }

    /**
     * @param string|array $expr
     * @param string       $like
     *
     * @return static
     */
    public function whereNotLike($expr, $like)
    {
        if ($like === '') {
            return $this;
        }

        if (is_array($expr)) {
            $conditions = [];
            /** @noinspection ForeachSourceInspection */
            foreach ($expr as $field) {
                $key = strtr($field, '.', '_');
                if (strpos($field, '.') !== false) {
                    $conditions[] = '[' . str_replace('.', '].[', $field) . ']' . ' NOT LIKE :' . $key;
                } else {
                    $conditions[] = '[' . $field . '] NOT LIKE :' . $key;
                }

                $this->_bind[$key] = $like;
            }

            $this->_conditions[] = implode(' AND ', $conditions);
        } else {
            $key = strtr($expr, '.', '_');

            if (strpos($expr, '[') === false && strpos($expr, '(') === false) {
                if (strpos($expr, '.') !== false) {
                    $expr = '[' . str_replace('.', '].[', $expr) . ']';
                } else {
                    $expr = '[' . $expr . ']';
                }
            }

            $this->_conditions[] = $expr . ' NOT LIKE :' . $key;

            $this->_bind[$key] = $like;
        }

        return $this;
    }

    /**
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     */
    public function whereContains($expr, $value)
    {
        return $value === '' ? $this : $this->whereLike($expr, '%' . $value . '%');
    }

    /**
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     */
    public function whereNotContains($expr, $value)
    {
        return $value === '' ? $this : $this->whereNotLike($expr, '%' . $value . '%');
    }

    /**
     * @param string|array $expr
     * @param string       $value
     * @param int          $length
     *
     * @return static
     */
    public function whereStartsWith($expr, $value, $length = null)
    {
        return $value === '' ? $this : $this->whereLike($expr, $length === null ? $value . '%' : str_pad($value, $length, '_'));
    }

    /**
     * @param string|array $expr
     * @param string       $value
     * @param int          $length
     *
     * @return static
     */
    public function whereNotStartsWith($expr, $value, $length = null)
    {
        return $value === '' ? $this : $this->whereNotLike($expr, $length === null ? $value . '%' : str_pad($value, $length, '_'));
    }

    /**
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     */
    public function whereEndsWith($expr, $value)
    {
        return $value === '' ? $this : $this->whereLike($expr, '%' . $value);
    }

    /**
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     */
    public function whereNotEndsWith($expr, $value)
    {
        return $value === '' ? $this : $this->whereNotLike($expr, '%' . $value);
    }

    /**
     * @param string $expr
     * @param string $regex
     * @param string $flags
     *
     * @return static
     */
    public function whereRegex($expr, $regex, $flags = '')
    {
        $key = $expr;
        $this->_conditions[] = $expr . ' REGEXP ' . (strpos($flags, 'i') !== false ? '' : 'BINARY ') . ':' . $key;
        $this->_bind[$key] = $regex;

        return $this;
    }

    /**
     * @param string $expr
     * @param string $regex
     * @param string $flags
     *
     * @return static
     */
    public function whereNotRegex($expr, $regex, $flags = '')
    {
        $key = $expr;
        $this->_conditions[] = $expr . ' NOT REGEXP ' . (strpos($flags, 'i') !== false ? '' : 'BINARY ') . ':' . $key;
        $this->_bind[$key] = $regex;

        return $this;
    }

    /**
     * @param string $expr
     *
     * @return static
     */
    public function whereNull($expr)
    {
        $this->_conditions[] = $expr . ' IS NULL';

        return $this;
    }

    /**
     * @param string $expr
     *
     * @return static
     */
    public function whereNotNull($expr)
    {
        $this->_conditions[] = $expr . ' IS NOT NULL';

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
        list($id_a, $id_b) = explode(',', $id);
        if (($pos = strpos($value, ',')) === false) {
            $this->_conditions[] = "$id_a=:$id_a OR $id_b=:$id_b";
            $this->_bind[$id_a] = $value;
            $this->_bind[$id_b] = $value;
        } else {
            $value_a = substr($value, 0, $pos);
            $value_b = substr($value, $pos + 1);

            $this->_conditions[] = "($id_a=:${id_a}_a AND $id_b=:${id_b}_b) OR ($id_a=:${id_a}_b AND $id_b=:${id_b}_a)";
            $this->_bind["${id_a}_a"] = $value_a;
            $this->_bind["${id_b}_b"] = $value_b;
            $this->_bind["${id_a}_b"] = $value_b;
            $this->_bind["${id_b}_a"] = $value_a;
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
            if (strpos($orderBy, '[') === false && strpos($orderBy, '(') === false) {
                $orderBy = (string)preg_replace('#\w+#', '[\\0]', $orderBy);
                $orderBy = str_ireplace(['[ASC]', '[DESC]'], ['ASC', 'DESC'], $orderBy);
            }
            $this->_order = $orderBy;
        } else {
            $r = '';
            /** @noinspection ForeachSourceInspection */
            foreach ($orderBy as $k => $v) {
                if (is_int($k)) {
                    $type = 'ASC';
                    $field = $v;
                } else {
                    $field = $k;
                    if (is_int($v)) {
                        $type = $v === SORT_ASC ? 'ASC' : 'DESC';
                    } else {
                        $type = strtoupper($v);
                    }
                }

                if (strpos($field, '[') === false && strpos($field, '(') === false) {
                    if (strpos($field, '.') !== false) {
                        $r .= '[' . str_replace('.', '].[', $field) . '] ' . $type . ', ';
                    } else {
                        $r .= '[' . $field . '] ' . $type . ', ';
                    }
                }
                $this->_order = substr($r, 0, -2);
            }
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
        if (is_array($having)) {
            if (count($having) === 1) {
                $this->_having = $having[0];
            } else {
                $items = [];
                /** @noinspection ForeachSourceInspection */
                foreach ($having as $item) {
                    $items[] = '(' . $item . ')';
                }
                $this->_having = implode(' AND ', $items);
            }
        } else {
            $this->_having = $having;
        }

        if ($bind) {
            $this->_bind = array_merge($this->_bind, $bind);
        }

        return $this;
    }

    /**
     * @param bool $forUpdate
     *
     * @return static
     */
    public function forUpdate($forUpdate = true)
    {
        $this->_forUpdate = (bool)$forUpdate;

        return $this;
    }

    /**
     * @param string|array $groupBy
     *
     * @return static
     */
    public function groupBy($groupBy)
    {
        if (is_string($groupBy)) {
            if (strpos($groupBy, '[') === false && strpos($groupBy, '(') === false) {
                $this->_group = preg_replace('#\w+#', '[\\0]', $groupBy);
            } else {
                $this->_group = $groupBy;
            }
        } else {
            $r = '';
            /** @noinspection ForeachSourceInspection */
            foreach ($groupBy as $item) {
                if (strpos($item, '[') === false && strpos($item, '(') === false) {
                    $r .= preg_replace('#\w+#', '[\\0]', $item) . ', ';
                } else {
                    $r .= $item . ', ';
                }
            }
            $this->_group = substr($r, 0, -2);
        }

        return $this;
    }

    /**
     * @param callable|string|array $indexBy
     *
     * @return static
     */
    public function indexBy($indexBy)
    {
        if (is_array($indexBy)) {
            $this->select([key($indexBy), current($indexBy)]);
        }

        $this->_index = $indexBy;

        return $this;
    }

    /**
     * @return string
     */
    protected function _getUnionSql()
    {
        $unions = [];

        /** @var \ManaPHP\Db\QueryInterface $queries */
        /** @noinspection ForeachSourceInspection */
        foreach ($this->_union['queries'] as $queries) {
            $unions[] = '(' . $queries->getSql() . ')';

            /** @noinspection SlowArrayOperationsInLoopInspection */
            $this->_bind = array_merge($this->_bind, $queries->getBind());
        }

        $sql = implode(' ' . $this->_union['type'] . ' ', $unions);

        $params = [];

        /**
         * Process order clause
         */
        if ($this->_order !== null) {
            $params['order'] = $this->_order;
        }

        /**
         * Process limit parameters
         */
        if ($this->_limit !== null) {
            $params['limit'] = $this->_limit;
        }

        if ($this->_offset !== null) {
            $params['offset'] = $this->_offset;
        }

        $sql .= $this->getConnection()->buildSql($params);

        $this->_tables[] = $queries->getTables()[0];

        return $sql;
    }

    /**
     * @return string
     */
    public function getSql()
    {
        if ($this->_sql === null) {
            $this->_sql = $this->_buildSql();
        }

        return $this->_sql;
    }

    /**
     * Returns a SQL statement built based on the builder parameters
     *
     * @return string
     */
    protected function _buildSql()
    {
        if ($this->_db === null || is_string($this->_db)) {
            $this->_db = $this->getConnection();
        }

        if ($this->_union) {
            return $this->_getUnionSql();
        }

        if (!$this->_tables) {
            if ($this->_model) {
                $this->_tables[] = $this->getSource();
            } else {
                throw new MisuseException('at least one model is required to build the query');
            }
        }

        foreach ($this->_tables as $alias => $table) {
            if (is_string($table) && strpos($table, '\\') !== false) {
                /** @var \ManaPHP\Model $model */
                $model = $this->_di->getShared($table);
                $this->_tables[$alias] = $model->getSource($this->_bind);
            }
        }

        $params = [];
        if ($this->_distinct) {
            $params['distinct'] = true;
        }

        if ($this->_fields !== null) {
            $fields = $this->_fields;
        } elseif (count($this->_tables) === 1) {
            $fields = $this->_model ? '[' . implode('], [', $this->_model->getFields()) . ']' : '*';
        } else {
            $fields = '';
            $selectedFields = [];
            foreach ($this->_tables as $alias => $table) {
                $selectedFields[] = '[' . (is_string($alias) ? $alias : $table) . '].*';
            }
            $fields .= implode(', ', $selectedFields);
        }
        $params['fields'] = $fields;

        $selectedTables = [];

        foreach ($this->_tables as $alias => $table) {
            if ($table instanceof $this) {
                if (is_int($alias)) {
                    throw new NotSupportedException('if using SubQuery, you must assign an alias for it');
                }

                $selectedTables[] = '(' . $table->getSql() . ') AS [' . $alias . ']';
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $this->_bind = array_merge($this->_bind, $table->getBind());
            } elseif (is_string($alias)) {
                $selectedTables[] = '[' . $table . '] AS [' . $alias . ']';
            } else {
                $selectedTables[] = '[' . $table . ']';
            }
        }

        $params['from'] = implode(', ', $selectedTables);

        $joinSQL = '';
        /** @noinspection ForeachSourceInspection */
        foreach ($this->_joins as $join) {
            list($joinTable, $joinCondition, $joinAlias, $joinType) = $join;

            if ($joinAlias !== null) {
                $this->_tables[$joinAlias] = $joinTable;
            } else {
                $this->_tables[] = $joinTable;
            }

            if ($joinType !== null) {
                $joinSQL .= ' ' . $joinType;
            }

            if ($joinTable instanceof $this) {
                $joinSQL .= ' JOIN (' . $joinTable->getSql() . ')';
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $this->_bind = array_merge($this->_bind, $joinTable->getBind());
                if ($joinAlias === null) {
                    throw new NotSupportedException('if using SubQuery, you must assign an alias for it');
                }
            } else {
                $joinSQL .= ' JOIN [' . $joinTable . ']';
            }

            if ($joinAlias !== null) {
                $joinSQL .= ' AS [' . $joinAlias . ']';
            }

            if ($joinCondition) {
                $joinSQL .= ' ON ' . $joinCondition;
            }
        }
        $params['join'] = $joinSQL;

        $wheres = [];
        foreach ($this->_conditions as $v) {
            $wheres[] = stripos($v, ' or ') ? "($v)" : $v;
        }

        if ($wheres) {
            $params['where'] = implode(' AND ', $wheres);
        }

        if ($this->_group !== null) {
            $params['group'] = $this->_group;
        }

        if ($this->_having !== null) {
            $params['having'] = $this->_having;
        }

        if ($this->_order !== null) {
            $params['order'] = $this->_order;
        }

        if ($this->_limit !== null) {
            $params['limit'] = $this->_limit;
        }

        if ($this->_offset !== null) {
            $params['offset'] = $this->_offset;
        }

        if ($this->_forUpdate) {
            $params['forUpdate'] = $this->_forUpdate;
        }

        $sql = $this->_db->buildSql($params);
        //compatible with other SQL syntax
        $replaces = [];
        foreach ($this->_bind as $key => $_) {
            $replaces[':' . $key . ':'] = ':' . $key;
        }

        $sql = strtr($sql, $replaces);

        foreach ($this->_tables as $table) {
            if (!$table instanceof $this) {
                $source = $table;
                if (strpos($source, '.')) {
                    $source = '[' . implode('].[', explode('.', $source)) . ']';
                } else {
                    $source = '[' . $source . ']';
                }
                $sql = str_replace('[' . $table . ']', $source, $sql);
            }
        }

        return $sql;
    }

    /**
     * @param string $key
     *
     * @return array
     */
    public function getBind($key = null)
    {
        if ($key !== null) {
            return isset($this->_bind[$key]) ? $this->_bind[$key] : null;
        } else {
            return $this->_bind;
        }
    }

    /**
     * @param array $bind
     * @param bool  $merge
     *
     * @return static
     */
    public function setBind($bind, $merge = true)
    {
        $this->_bind = $merge ? array_merge($this->_bind, $bind) : $bind;

        return $this;
    }

    /**
     * @return array
     */
    public function getTables()
    {
        return $this->_tables;
    }

    /**
     *
     * @return array
     */
    public function execute()
    {
        $this->_hiddenParamNumber = 0;

        $this->_sql = $this->_buildSql();

        if (in_array('FALSE', $this->_conditions, true)) {
            $this->logger->debug($this->_sql, 'db.query.skip');
            return [];
        }

        $result = $this->getConnection()->fetchAll($this->_sql, $this->_bind, \PDO::FETCH_ASSOC, $this->_force_master);

        $indexBy = $this->_index;

        if ($indexBy === null) {
            return $result;
        }

        $rows = [];
        if (is_scalar($indexBy)) {
            foreach ($result as $row) {
                $rows[$row[$indexBy]] = $row;
            }
        } elseif (is_array($indexBy)) {
            $k = key($indexBy);
            $v = current($indexBy);
            foreach ($result as $row) {
                $rows[$row[$k]] = $row[$v];
            }
        } else {
            foreach ($result as $row) {
                $rows[$indexBy($row)] = $row;
            }
        }

        return $rows;
    }

    /**
     * @param array $expr
     *
     * @return array
     */
    public function aggregate($expr)
    {
        $fields = '';

        foreach ($expr as $k => $v) {
            if (is_int($k)) {
                $fields .= '[' . $v . '], ';
            } else {
                if (preg_match('#^(\w+)\(([\w]+)\)$#', $v, $matches) === 1) {
                    $fields .= strtoupper($matches[1]) . '([' . $matches[2] . '])';
                } else {
                    $fields .= $v;
                }

                $fields .= ' AS [' . $k . '], ';
            }
        }

        if ($this->_group) {
            $this->_fields = $fields . $this->_group;
        } else {
            $this->_fields = substr($fields, 0, -2);
        }

        return $this->execute();
    }

    /**
     * @param string $field
     *
     * @return int
     */
    public function count($field = '*')
    {
        if ($this->_union) {
            throw new NotSupportedException('Union query is not support to get total rows');
        }

        $copy = clone $this;

        $copy->_fields = "COUNT($field) as [row_count]";
        $copy->_limit = null;
        $copy->_offset = null;
        $copy->_order = null;
        $copy->_index = null;

        $copy->_sql = $copy->_buildSql();

        if ($copy->_group === null) {
            $result = $copy->getConnection()->fetchOne($copy->_sql, $copy->_bind);

            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $rowCount = (int)$result['row_count'];
        } else {
            $result = $copy->getConnection()->fetchAll($copy->_sql, $copy->_bind);
            $rowCount = count($result);
        }

        return $rowCount;
    }


    /**
     * @return bool
     */
    public function exists()
    {
        $this->_fields = '1 as [stub]';
        $this->_limit = 1;
        $this->_offset = 0;

        $rs = $this->execute();

        return isset($rs[0]);
    }

    /**
     * @param \ManaPHP\Db\QueryInterface[] $queries
     * @param bool                         $distinct
     *
     * @return static
     */
    public function union($queries, $distinct = false)
    {
        if ($this->_db === null) {
            foreach ($queries as $query) {
                if ($query instanceof self) {
                    $this->_db = $query->_db;
                    break;
                }
            }
        }
        $this->_union = ['type' => 'UNION ' . ($distinct ? 'DISTINCT' : 'ALL'), 'queries' => $queries];

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        try {
            return $this->getSql();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * @return array|false
     * @deprecated
     */
    public function fetchOne()
    {
        $r = $this->limit(1)->execute();

        return $r ? $r[0] : false;
    }

    /**
     * @return array
     * @deprecated
     */
    public function fetchAll()
    {
        return $this->execute();
    }

    /**
     * @param string $field
     *
     * @return array
     */
    public function values($field)
    {
        $values = [];
        foreach ($this->distinct()->select([$field])->all() as $v) {
            $values[] = $v[$field];
        }

        return $values;
    }

    /**
     * @param array $fieldValues
     *
     * @return int
     */
    public function update($fieldValues)
    {
        foreach ($fieldValues as $field => $value) {
            if ($value instanceof ExpressionInterface) {
                if ($value instanceof Increment) {
                    $fieldValues[] = "[$field]=[$field]" . ($value->step >= 0 ? '+' : '') . $value->step;
                } elseif ($value instanceof Raw) {
                    $fieldValues[] = "[$field]=" . $value->expression;
                }
                unset($fieldValues[$field]);
            }
        }

        return $this->getConnection()->update($this->getSource(), $fieldValues, $this->_conditions, $this->_bind);
    }

    /**
     * @return int
     */
    public function delete()
    {
        return $this->getConnection()->delete($this->getSource(), $this->_conditions, $this->_bind);
    }
}
