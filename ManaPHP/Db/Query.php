<?php
namespace ManaPHP\Db;

use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Helper\Arr;
use ManaPHP\Helper\Sharding\ShardingTooManyException;
use ManaPHP\Model\Expression\Decrement;
use ManaPHP\Model\Expression\Increment;
use ManaPHP\Model\Expression\Raw;
use ManaPHP\Model\ExpressionInterface;
use PDO;

class Query extends \ManaPHP\Query implements QueryInterface
{
    /**
     * @var string
     */
    protected $_table;

    /**
     * @var string
     */
    protected $_alias;

    /**
     * @var array
     */
    protected $_joins = [];

    /**
     * @var array
     */
    protected $_conditions = [];

    /**
     * @var array
     */
    protected $_having;

    /**
     * @var bool
     */
    protected $_for_update;

    /**
     * @var array
     */
    protected $_bind = [];

    /**
     * @var int
     */
    protected $_param_number = 0;

    /**
     * @var string
     */
    protected $_sql;

    /**
     * @param \ManaPHP\DbInterface|string $db
     */
    public function __construct($db = 'db')
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
     * @param string $db
     *
     * @return \ManaPHP\DbInterface
     */
    protected function _getDb($db)
    {
        return $db === '' ? $this->_db : $this->_di->getShared($db);
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
            if (strpos($table, '\\') !== false) {
                $this->setModel($table);
                $table = $this->_model->getSource();
            }

            $this->_table = $table;
            $this->_alias = $alias;
        }

        return $this;
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
        if (strpos($condition, '[') === false && strpos($condition, '(') === false) {
            $condition = (string)preg_replace('#\w+#', '[\\0]', $condition);
        }

        if (strpos($table, '\\') !== false) {
            /** @var \ManaPHP\Model $model */
            $model = $this->_di->getShared($table);
            $table = $model->getSource();
        }

        $this->_joins[] = [$table, $condition, $alias, $type];

        return $this;
    }

    /**
     * @param string $table
     * @param string $condition
     * @param string $alias
     *
     * @return static
     */
    public function innerJoin($table, $condition = null, $alias = null)
    {
        return $this->join($table, $condition, $alias, 'INNER');
    }

    /**
     * @param string $table
     * @param string $condition
     * @param string $alias
     *
     * @return static
     */
    public function leftJoin($table, $condition = null, $alias = null)
    {
        return $this->join($table, $condition, $alias, 'LEFT');
    }

    /**
     * @param string $table
     * @param string $condition
     * @param string $alias
     *
     * @return static
     */
    public function rightJoin($table, $condition = null, $alias = null)
    {
        return $this->join($table, $condition, $alias, 'RIGHT');
    }

    /**
     * @param string $field
     * @param mixed  $value
     *
     * @return static
     */
    public function whereEq($field, $value)
    {
        $normalizedField = preg_replace('#\w+#', '[\\0]', $field);

        if ($value === null) {
            $this->_conditions[] = $normalizedField . ' IS NULL';
        } else {
            $this->_shard_context[$field] = $value;

            $bind_key = strpos($field, '.') !== false ? strtr($field, '.', '_') : $field;
            $this->_conditions[] = "$normalizedField=:$bind_key";
            $this->_bind[$bind_key] = $value;
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
        if (in_array($operator, ['>=', '>', '<', '<='], true)) {
            $this->_shard_context[$field] = [$operator, $value];
        }

        $bind_key = strpos($field, '.') ? strtr($field, '.', '_') : $field;
        $normalizedField = preg_replace('#\w+#', '[\\0]', $field);

        if ($operator === '=') {
            return $this->whereEq($field, $value);
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

        $this->_shard_context[$expr] = ['~=', [$min, $max]];

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
            $minKey = '_min_' . $this->_param_number;
            $maxKey = '_max_' . $this->_param_number;
            $this->_param_number++;
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

        $minKey = '_min_' . $this->_param_number;
        $maxKey = '_max_' . $this->_param_number;

        $this->_param_number++;

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
     * @param string $expr
     * @param array  $values
     *
     * @return static
     */
    public function whereIn($expr, $values)
    {
        if ($values) {
            $this->_shard_context[$expr] = $values;

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
                foreach ($values as $k => $value) {
                    $key = '_in_' . $this->_param_number . '_' . $k;
                    $bindKeys[] = ":$key";
                    $this->_bind[$key] = $value;
                }

                $this->_conditions[] = $expr . ' IN (' . implode(', ', $bindKeys) . ')';
                $this->_param_number++;
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
     * @param string $expr
     * @param array  $values
     *
     * @return static
     */
    public function whereNotIn($expr, $values)
    {
        if ($values) {
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
                foreach ($values as $k => $value) {
                    $key = '_in_' . $this->_param_number . '_' . $k;
                    $bindKeys[] = ':' . $key;
                    $this->_bind[$key] = $value;
                }

                $this->_param_number++;

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
        $this->_for_update = (bool)$forUpdate;

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
            $this->_group = preg_split('#[\s,]+#', $groupBy, -1, PREG_SPLIT_NO_EMPTY);
        } else {
            $this->_group = $groupBy;
        }

        return $this;
    }

    /**
     * @param array $group
     *
     * @return string
     */
    protected function _buildGroup($group)
    {
        $r = '';
        foreach ($group as $item) {
            if (strpos($item, '[') === false && strpos($item, '(') === false) {
                $r .= preg_replace('#\w+#', '[\\0]', $item) . ', ';
            } else {
                $r .= $item . ', ';
            }
        }

        return substr($r, 0, -2);
    }

    /**
     * @return string
     */
    public function getSql()
    {
        if ($this->_sql === null) {
            $shards = $this->getShards();

            $tables = current($shards);
            if (count($tables) !== 1) {
                throw new ShardingTooManyException(__METHOD__);
            }
            $db = $this->_getDb(key($shards));
            $this->_sql = $this->_buildSql($db, $tables[0]);
        }

        return $this->_sql;
    }

    /**
     * @param array $order
     *
     * @return string
     */
    protected function _buildOrder($order)
    {
        $r = '';

        foreach ($order as $field => $v) {
            $type = $v === SORT_ASC ? 'ASC' : 'DESC';

            if (strpos($field, '[') === false && strpos($field, '(') === false) {
                if (strpos($field, '.') !== false) {
                    $r .= '[' . str_replace('.', '].[', $field) . '] ' . $type . ', ';
                } else {
                    $r .= '[' . $field . '] ' . $type . ', ';
                }
            } else {
                $r .= "$field $type, ";
            }
        }

        return substr($r, 0, -2);
    }

    /**
     * Returns a SQL statement built based on the builder parameters
     *
     * @param \ManaPHP\DbInterface $db
     * @param string               $table
     *
     * @return string
     */
    protected function _buildSql($db, $table)
    {
        if (!$this->_table) {
            throw new MisuseException('at least one model is required to build the query');
        }

        $params = [];
        if ($this->_distinct) {
            $params['distinct'] = true;
        }

        if ($this->_fields !== null) {
            $fields = $this->_fields;
        } elseif ($this->_joins) {
            $fields = '*';
        } else {
            $fields = $this->_model ? '[' . implode('], [', $this->_model->getFields()) . ']' : '*';
        }
        $params['fields'] = $fields;

        $alias = $this->_alias;
        $params['from'] = $alias ? "[$table] AS [$alias]" : $table;

        $joinSQL = '';
        foreach ($this->_joins as $join) {
            list($joinTable, $joinCondition, $joinAlias, $joinType) = $join;

            if ($joinType !== null) {
                $joinSQL .= ' ' . $joinType;
            }

            $joinSQL .= ' JOIN [' . $joinTable . ']';

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

        if ($this->_group) {
            $params['group'] = $this->_buildGroup($this->_group);
        }

        if ($this->_having !== null) {
            $params['having'] = $this->_having;
        }

        if ($this->_order) {
            $params['order'] = $this->_buildOrder($this->_order);
        }

        if ($this->_limit !== null) {
            $params['limit'] = $this->_limit;
        }

        if ($this->_offset !== null) {
            $params['offset'] = $this->_offset;
        }

        if ($this->_for_update) {
            $params['forUpdate'] = $this->_for_update;
        }

        $sql = $db->buildSql($params);
        //compatible with other SQL syntax
        $replaces = [];
        foreach ($this->_bind as $key => $_) {
            $replaces[':' . $key . ':'] = ':' . $key;
        }

        return strtr($sql, $replaces);
    }

    /**
     * @param string $key
     *
     * @return array
     */
    public function getBind($key = null)
    {
        if ($key !== null) {
            return $this->_bind[$key] ?? null;
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
     * @param string $db
     * @param string $table
     *
     * @return array
     */
    protected function _query($db, $table)
    {
        $connection = $this->_getDb($db);

        $this->_sql = $this->_buildSql($connection, $table);

        if (in_array('FALSE', $this->_conditions, true)) {
            $this->logger->debug($this->_sql, 'db.query.skip');
            return [];
        }

        return $connection->fetchAll($this->_sql, $this->_bind, PDO::FETCH_ASSOC, $this->_force_master);
    }

    /**
     * @return array
     */
    public function execute()
    {
        $shards = $this->getShards();

        $result = [];
        if (count($shards) === 1 && count(current($shards)) === 1) {
            $result = $this->_query(key($shards), current($shards)[0]);
        } elseif ($this->_order) {
            $valid_times = 0;
            foreach ($shards as $db => $tables) {
                foreach ($tables as $table) {
                    $copy = clone $this;

                    if ($copy->_limit) {
                        $copy->_limit += $copy->_offset;
                        $copy->_offset = 0;
                    }

                    if ($r = $copy->_query($db, $table)) {
                        $valid_times++;
                        $result = $result ? array_merge($result, $r) : $r;
                    }
                }
            }

            if ($valid_times > 1) {
                $result = Arr::sort($result, $this->_order);
            }

            $result = $this->_limit ? array_slice($result, $this->_offset, $this->_limit) : $result;
        } elseif ($this->_limit) {
            foreach ($shards as $db => $tables) {
                foreach ($tables as $table) {
                    if ($r = $this->_query($db, $table)) {
                        $result = $result ? array_merge($result, $r) : $r;
                        if (count($result) >= $this->_offset + $this->_limit) {
                            return array_slice($result, (int)$this->_offset, $this->_limit);
                        }
                    }
                }
            }

            $result = $result ? array_slice($result, (int)$this->_offset, $this->_limit) : [];
        } else {
            foreach ($shards as $db => $tables) {
                foreach ($tables as $table) {
                    if ($r = $this->_query($db, $table)) {
                        $result = $result ? array_merge($result, $r) : $r;
                    }
                }
            }
        }

        return $this->_index ? Arr::indexby($result, $this->_index) : $result;
    }

    /**
     * @param array $expr
     * @param array $group
     *
     * @return string
     */
    protected function _buildAggregate($expr, $group)
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

        if ($group) {
            foreach ($group as $k => $v) {
                $fields .= is_int($k) ? "[$v], " : "$v, ";
            }
        }

        return substr($fields, 0, -2);
    }

    /**
     * @param array $expr
     *
     * @return array
     */
    public function aggregate($expr)
    {
        $this->_aggregate = $expr;

        $shards = $this->getShards();

        if (count($shards) === 1 && count(current($shards)) === 1) {
            $this->_fields = $this->_buildAggregate($expr, $this->_group);

            $result = $this->_query(key($shards), current($shards)[0]);
        } else {
            if ($this->_having) {
                throw new NotSupportedException('sharding not support having');
            }

            $aggs = [];
            foreach ($this->_aggregate as $k => $v) {
                if (preg_match('#^\w+#', $v, $match) !== 1) {
                    throw new NotSupportedException($v);
                }

                $agg = strtoupper($match[0]);
                $aggs[$k] = $agg;
                if (in_array($agg, ['COUNT', 'MAX', 'MIN', 'SUM'])) {
                    null;
                } elseif ($agg === 'AVG') {
                    $sum = $k . '_sum';
                    if (!isset($this->_aggregate[$sum])) {
                        $this->_aggregate[$sum] = 'SUM(' . substr($v, 4);
                    }

                    $count = $k . '_count';
                    if (!isset($this->_aggregate[$count])) {
                        $this->_aggregate[$count] = 'COUNT(*)';
                    }
                } else {
                    throw new NotSupportedException($v);
                }
            }

            $this->_fields = $this->_buildAggregate($this->_aggregate, $this->_group);

            $rows = [];
            foreach ($shards as $db => $tables) {
                foreach ($tables as $table) {
                    if ($r = $this->_query($db, $table)) {
                        $rows = $rows ? array_merge($rows, $r) : $r;
                    }
                }
            }

            $result = Arr::aggregate($rows, $aggs, $this->_group ?? []);
        }

        if ($this->_order) {
            $result = Arr::sort($result, $this->_order);
        }

        return $this->_index ? Arr::indexby($result, $this->_index) : $result;
    }

    /**
     * @param string $field
     *
     * @return int
     */
    public function count($field = '*')
    {
        if ($this->_group) {
            throw new NotSupportedException('group is not support to get total rows');
        }

        $row_count = 0;
        $shards = $this->getShards();

        foreach ($shards as $db => $tables) {
            $connection = $this->_getDb($db);
            foreach ($tables as $table) {
                $copy = clone $this;

                $copy->_fields = "COUNT($field) as [row_count]";
                $copy->_limit = null;
                $copy->_offset = null;
                $copy->_order = null;
                $copy->_index = null;

                $copy->_sql = $copy->_buildSql($connection, $table);

                $result = $connection->fetchOne($copy->_sql, $copy->_bind);
                $row_count += $result['row_count'];
            }
        }

        return $row_count;
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
     * @param string $field
     *
     * @return array
     */
    public function values($field)
    {
        $values = [];

        $shards = $this->getShards();

        if (count($shards) > 1 || count(current($shards)) > 1) {
            foreach ($this->distinct()->select([$field])->all() as $v) {
                $value = $v[$field];
                if (!in_array($value, $values, true)) {
                    $values[] = $value;
                }
            }

            if ($this->_order) {
                if (current($this->_order) === SORT_ASC) {
                    sort($values);
                } else {
                    rsort($values);
                }
            }
        } else {
            foreach ($this->distinct()->select([$field])->all() as $v) {
                $values[] = $v[$field];
            }
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
                    $fieldValues[] = "[$field]=[$field]" . ($value->step >= 0 ? '+' : '-') . abs($value->step);
                } elseif ($value instanceof Decrement) {
                    $fieldValues[] = "[$field]=[$field]" . ($value->step >= 0 ? '-' : '+') . abs($value->step);
                } elseif ($value instanceof Raw) {
                    $fieldValues[] = "[$field]=" . $value->expression;
                }
                unset($fieldValues[$field]);
            }
        }

        $shards = $this->getShards();

        $affected_count = 0;
        foreach ($shards as $db => $tables) {
            $db = $this->_getDb($db);
            foreach ($tables as $table) {
                $affected_count += $db->update($table, $fieldValues, $this->_conditions, $this->_bind);
            }
        }

        return $affected_count;
    }

    /**
     * @return int
     */
    public function delete()
    {
        $shards = $this->getShards();

        $affected_count = 0;
        foreach ($shards as $db => $tables) {
            $db = $this->_getDb($db);
            foreach ($tables as $table) {
                $affected_count += $db->delete($table, $this->_conditions, $this->_bind);
            }
        }

        return $affected_count;
    }
}
