<?php

namespace ManaPHP\Db;

use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Helper\Arr;
use ManaPHP\Helper\Sharding;
use ManaPHP\Helper\Sharding\ShardingTooManyException;
use PDO;

class Query extends \ManaPHP\Query
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
                $table = $this->_model->getTable();
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

        $this->_joins[] = [$table, $condition, $alias, $type];

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
     * @param string           $field
     * @param int|float|string $min
     * @param int|float|string $max
     *
     * @return static
     */
    public function whereBetween($field, $min, $max)
    {
        if ($min === null || $min === '') {
            return $max === null || $max === '' ? $this : $this->whereCmp($field, '<=', $max);
        } elseif ($max === null || $max === '') {
            return $min === null || $min === '' ? $this : $this->whereCmp($field, '>=', $min);
        }

        $this->_shard_context[$field] = ['~=', [$min, $max]];

        $id = strtr($field, '.', '_');
        $field = '[' . str_replace('.', '].[', $field) . ']';

        $min_key = $id . '_min';
        $max_key = $id . '_max';

        $this->_conditions[] = "$field BETWEEN :$min_key AND :$max_key";

        $this->_bind[$min_key] = $min;
        $this->_bind[$max_key] = $max;

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
        if ($min === null || $min === '') {
            return $max === null || $max === '' ? $this : $this->whereCmp($field, '>', $max);
        } elseif ($max === null || $max === '') {
            return $min === null || $min === '' ? $this : $this->whereCmp($field, '<', $min);
        }

        $id = strtr($field, '.', '_');
        $field = '[' . str_replace('.', '].[', $field) . ']';

        $min_key = $id . '_min';
        $max_key = $id . '_max';

        $this->_conditions[] = "$field NOT BETWEEN :$min_key AND :$max_key";

        $this->_bind[$min_key] = $min;
        $this->_bind[$max_key] = $max;

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
        if ($values) {
            $this->_shard_context[$field] = $values;

            $id = str_replace('.', '_', $field);
            $field = '[' . str_replace('.', '].[', $field) . ']';

            if (is_int(current($values))) {
                $this->_conditions[] = $field . ' IN (' . implode(', ', array_map('intval', $values)) . ')';
            } else {
                $bindKeys = [];
                foreach ($values as $k => $value) {
                    $key = "{$id}_in_{$k}";
                    $bindKeys[] = ":$key";
                    $this->_bind[$key] = $value;
                }

                $this->_conditions[] = $field . ' IN (' . implode(', ', $bindKeys) . ')';
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
     * @param string $field
     * @param array  $values
     *
     * @return static
     */
    public function whereNotIn($field, $values)
    {
        if ($values) {
            $id = str_replace('.', '_', $field);
            $field = '[' . str_replace('.', '].[', $field) . ']';

            if (is_int(current($values))) {
                $this->_conditions[] = $field . ' NOT IN (' . implode(', ', array_map('intval', $values)) . ')';
            } else {
                $bindKeys = [];
                foreach ($values as $k => $value) {
                    $key = "{$id}_not_in_{$k}";
                    $bindKeys[] = ':' . $key;
                    $this->_bind[$key] = $value;
                }

                $this->_conditions[] = $field . ' NOT IN (' . implode(', ', $bindKeys) . ')';
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
     * @param string|array $fields
     * @param string       $like
     *
     * @return static
     */
    public function whereLike($fields, $like)
    {
        if ($like === '') {
            return $this;
        }

        if (is_string($fields) && strpos($fields, ',') !== false) {
            $fields = preg_split('#[\s,]+#', $fields, -1, PREG_SPLIT_NO_EMPTY);
        }

        if (is_array($fields)) {
            $conditions = [];
            foreach ($fields as $field) {
                $key = strtr($field, '.', '_');
                $conditions[] = '[' . str_replace('.', '].[', $field) . ']' . ' LIKE :' . $key;
                $this->_bind[$key] = $like;
            }

            $this->_conditions[] = implode(' OR ', $conditions);
        } else {
            $key = strtr($fields, '.', '_');
            $fields = '[' . str_replace('.', '].[', $fields) . ']';
            $this->_conditions[] = $fields . ' LIKE :' . $key;
            $this->_bind[$key] = $like;
        }

        return $this;
    }

    /**
     * @param string|array $fields
     * @param string       $like
     *
     * @return static
     */
    public function whereNotLike($fields, $like)
    {
        if ($like === '') {
            return $this;
        }

        if (is_string($fields) && strpos($fields, ',') !== false) {
            $fields = preg_split('#[\s,]+#', $fields, -1, PREG_SPLIT_NO_EMPTY);
        }

        if (is_array($fields)) {
            $conditions = [];
            foreach ($fields as $field) {
                $key = strtr($field, '.', '_');
                $conditions[] = '[' . str_replace('.', '].[', $field) . ']' . ' NOT LIKE :' . $key;
                $this->_bind[$key] = $like;
            }

            $this->_conditions[] = implode(' AND ', $conditions);
        } else {
            $key = strtr($fields, '.', '_');
            $fields = '[' . str_replace('.', '].[', $fields) . ']';
            $this->_conditions[] = $fields . ' NOT LIKE :' . $key;
            $this->_bind[$key] = $like;
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
        return $value === '' ? $this : $this->whereLike($fields, '%' . $value . '%');
    }

    /**
     * @param string|array $fields
     * @param string       $value
     *
     * @return static
     */
    public function whereNotContains($fields, $value)
    {
        return $value === '' ? $this : $this->whereNotLike($fields, '%' . $value . '%');
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
        return $value === '' ? $this : $this->whereLike($fields, $length === null ? $value . '%' : str_pad($value, $length, '_'));
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
        return $value === '' ? $this : $this->whereNotLike($fields, $length === null ? $value . '%' : str_pad($value, $length, '_'));
    }

    /**
     * @param string|array $fields
     * @param string       $value
     *
     * @return static
     */
    public function whereEndsWith($fields, $value)
    {
        return $value === '' ? $this : $this->whereLike($fields, '%' . $value);
    }

    /**
     * @param string|array $fields
     * @param string       $value
     *
     * @return static
     */
    public function whereNotEndsWith($fields, $value)
    {
        return $value === '' ? $this : $this->whereNotLike($fields, '%' . $value);
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
        $key = strtr($field, '.', '_');
        $this->_conditions[] = '[' . str_replace('.', '].[', $field) . '] REGEXP ' . (strpos($flags, 'i') !== false ? '' : 'BINARY ') . ':' . $key;
        $this->_bind[$key] = $regex;

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
        $key = strtr($field, '.', '_');
        $this->_conditions[] = '[' . str_replace('.', '].[', $field) . '] NOT REGEXP ' . (strpos($flags, 'i') !== false ? '' : 'BINARY ') . ':' . $key;
        $this->_bind[$key] = $regex;

        return $this;
    }

    /**
     * @param string $field
     *
     * @return static
     */
    public function whereNull($field)
    {
        $this->_conditions[] = '[' . str_replace('.', '].[', $field) . '] IS NULL';

        return $this;
    }

    /**
     * @param string $field
     *
     * @return static
     */
    public function whereNotNull($field)
    {
        $this->_conditions[] = '[' . str_replace('.', '].[', $field) . '] IS NOT NULL';

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
        $key_a = str_replace('.', '_', $id_a);
        $key_b = str_replace('.', '_', $id_b);
        $id_a = '[' . str_replace('.', '].[', $id_a) . ']';
        $id_b = '[' . str_replace('.', '].[', $id_b) . ']';
        if (($pos = strpos($value, ',')) === false) {
            $this->_conditions[] = "$id_a=:$id_a OR $id_b=:$id_b";
            $this->_bind[$id_a] = $value;
            $this->_bind[$id_b] = $value;
        } else {
            $value_a = substr($value, 0, $pos);
            $value_b = substr($value, $pos + 1);
            $this->_conditions[] = "($id_a=:${key_a}_a AND $id_b=:${key_b}_b) OR ($id_a=:${key_a}_b AND $id_b=:${key_b}_a)";
            $this->_bind["${key_a}_a"] = $value_a;
            $this->_bind["${key_b}_b"] = $value_b;
            $this->_bind["${key_a}_b"] = $value_b;
            $this->_bind["${key_b}_a"] = $value_a;
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
            $this->_sql = $this->_buildSql($db, $tables[0], $this->_joins);
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
                $r .= '[' . str_replace('.', '].[', $field) . '] ' . $type . ', ';
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
     * @param array                $joins
     *
     * @return string
     */
    protected function _buildSql($db, $table, $joins)
    {
        if (!$this->_table && !$this->_model) {
            throw new MisuseException('at least one model is required to build the query');
        }

        $params = [];
        if ($this->_distinct) {
            $params['distinct'] = true;
        }

        if ($this->_fields !== null) {
            $fields = $this->_fields;
        } elseif ($joins) {
            $fields = '*';
        } else {
            $fields = $this->_model ? '[' . implode('], [', $this->_model->getFields()) . ']' : '*';
        }
        $params['fields'] = $fields;

        $table = '[' . str_replace('.', '].[', $table) . ']';
        $alias = $this->_alias;
        $params['from'] = $alias ? "$table AS [$alias]" : $table;

        $joinSQL = '';
        foreach ($joins as list($joinTable, $joinCondition, $joinAlias, $joinType)) {
            if ($joinType !== null) {
                $joinSQL .= ' ' . $joinType;
            }

            $joinTable = '[' . str_replace('.', '].[', $joinTable) . ']';
            $joinSQL .= " JOIN $joinTable";

            if ($joinAlias !== null) {
                $joinSQL .= ' AS [' . $joinAlias . ']';
            }

            if ($joinCondition) {
                $joinSQL .= ' ON ' . $joinCondition;
            }
        }
        $params['join'] = $joinSQL;

        if (count($this->_conditions) === 1) {
            $params['where'] = $this->_conditions[0];
        } elseif ($this->_conditions) {
            $wheres = [];
            foreach ($this->_conditions as $v) {
                $wheres[] = stripos($v, ' or ') ? "($v)" : $v;
            }

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

        if ($this->_joins) {
            $joins = [];
            foreach ($this->_joins as $k => $join) {
                $join_table = $join[0];
                if (strpos($join_table, '\\') !== false) {
                    /** @var \ManaPHP\Db\ModelInterface $model */
                    $model = $join_table::sample();
                    $join_shards = $model->getMultipleShards($this->_shard_context);
                } else {
                    $db = is_object($this->_db) ? '' : (string)$this->_db;
                    if ($shard_strategy = $this->_shard_strategy) {
                        $join_shards = $shard_strategy($db, $join_table, $this->_shard_context);
                    } else {
                        $join_shards = Sharding::multiple($db, $join_table, $this->_shard_context);
                    }
                }

                if (!isset($join_shards[$db])) {
                    throw new NotSupportedException('');
                }

                $join_tables = $join_shards[$db];
                if (count($join_tables) > 1) {
                    throw new NotSupportedException('');
                }

                $join[0] = $join_tables[0];
                $joins[] = $join;
            }
        } else {
            $joins = [];
        }

        $this->_sql = $this->_buildSql($connection, $table, $joins);

        return $connection->fetchAll($this->_sql, $this->_bind, PDO::FETCH_ASSOC, $this->_force_master);
    }

    /**
     * @return array
     */
    public function execute()
    {
        if (in_array('FALSE', $this->_conditions, true)) {
            $this->logger->debug($this->_sql, 'db.query.skip');
            return [];
        }

        if ($this->_joins) {
            foreach ($this->_shard_context as $k => $v) {
                if (($pos = strpos($k, '.')) !== false) {
                    $nk = substr($k, $pos + 1);
                    $this->_shard_context[$nk] = $v;
                }
            }
        }

        $shards = $this->getShards();

        $result = [];
        if (count($shards) === 1 && count(current($shards)) === 1) {
            $result = $this->_query(key($shards), current($shards)[0]);
        } elseif ($this->_order) {
            $copy = clone $this;

            if ($copy->_limit) {
                $copy->_limit += $copy->_offset;
                $copy->_offset = 0;
            }

            $valid_times = 0;
            foreach ($shards as $db => $tables) {
                foreach ($tables as $table) {
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
                            $result = array_slice($result, (int)$this->_offset, $this->_limit);
                            return $this->_index ? Arr::indexby($result, $this->_index) : $result;
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
        $copy = clone $this;

        $copy->_fields = "COUNT($field) as [row_count]";
        $copy->_limit = null;
        $copy->_offset = null;
        $copy->_order = null;
        $copy->_index = null;

        $row_count = 0;
        $shards = $this->getShards();
        foreach ($shards as $db => $tables) {
            foreach ($tables as $table) {
                $result = $copy->_query($db, $table);
                $row_count += $this->_group ? count($result) : $result[0]['row_count'];
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
        $this->distinct()->select([$field]);

        $values = [];

        $shards = $this->getShards();
        if (count($shards) === 1 && count(current($shards)) === 1) {
            $db = key($shards);
            $table = current($shards)[0];
            foreach ($this->_query($db, $table) as $row) {
                $values[] = $row[$field];
            }
        } else {
            foreach ($shards as $db => $tables) {
                foreach ($tables as $table) {
                    foreach ($this->_query($db, $table) as $row) {
                        $value = $row[$field];
                        if (!in_array($value, $values, true)) {
                            $values[] = $value;
                        }
                    }
                }
            }

            if ($this->_order) {
                if (current($this->_order) === SORT_ASC) {
                    sort($values);
                } else {
                    rsort($values);
                }
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
