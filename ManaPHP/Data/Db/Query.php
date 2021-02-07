<?php

namespace ManaPHP\Data\Db;

use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Helper\Arr;
use ManaPHP\Helper\Sharding;
use ManaPHP\Helper\Sharding\ShardingTooManyException;
use PDO;

class Query extends \ManaPHP\Data\Query
{
    /**
     * @var array
     */
    protected $joins = [];

    /**
     * @var array
     */
    protected $conditions = [];

    /**
     * @var array
     */
    protected $having;

    /**
     * @var bool
     */
    protected $for_update;

    /**
     * @var array
     */
    protected $bind = [];

    /**
     * @var string
     */
    protected $sql;

    /**
     * @param \ManaPHP\Data\DbInterface|string $db
     */
    public function __construct($db = 'db')
    {
        $this->db = $db;
    }

    /**
     * @param \ManaPHP\Data\DbInterface|string $db
     *
     * @return static
     */
    public function setDb($db)
    {
        $this->db = $db;

        return $this;
    }

    /**
     * @param string $db
     *
     * @return \ManaPHP\Data\DbInterface
     */
    protected function getDb($db)
    {
        return $db === '' ? $this->db : $this->getShared($db);
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
            if (strpbrk($v, '[(') === false) {
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
        $this->fields = substr($r, 0, -2);

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
        if (strpbrk($condition, '[(') === false) {
            $condition = (string)preg_replace('#\w+#', '[\\0]', $condition);
        }

        $this->joins[] = [$table, $condition, $alias, $type];

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
            $this->conditions[] = $normalizedField . ' IS NULL';
        } else {
            $this->shard_context[$field] = $value;

            $bind_key = str_contains($field, '.') ? strtr($field, '.', '_') : $field;
            $this->conditions[] = "$normalizedField=:$bind_key";
            $this->bind[$bind_key] = $value;
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
            $this->shard_context[$field] = [$operator, $value];
        }

        $bind_key = strpos($field, '.') ? strtr($field, '.', '_') : $field;
        $normalizedField = preg_replace('#\w+#', '[\\0]', $field);

        if ($operator === '=') {
            return $this->whereEq($field, $value);
        } elseif ($operator === '~=') {
            if ($value === 0 || $value === 0.0) {
                $this->conditions[] = "$normalizedField IS NULL OR $normalizedField=0";
            } elseif ($value === '') {
                $this->conditions[] = "$normalizedField IS NULL OR $normalizedField=''";
            } else {
                $this->conditions[] = $normalizedField . '=' . $bind_key;
                $this->bind[$bind_key] = $value;
            }
        } elseif ($operator === '!=' || $operator === '<>') {
            if ($value === null) {
                $this->conditions[] = $normalizedField . ' IS NOT NULL';
            } else {
                $this->conditions[] = $normalizedField . $operator . ':' . $bind_key;
                $this->bind[$bind_key] = $value;
            }
        } elseif (in_array($operator, ['>', '>=', '<', '<='], true)) {
            $this->conditions[] = $normalizedField . $operator . ':' . $bind_key;
            $this->bind[$bind_key] = $value;
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

        $this->conditions[] = "$field%$divisor=$remainder";

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
        $this->conditions[] = $expr;

        if (is_array($bind)) {
            $this->bind = array_merge($this->bind, $bind);
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
            return $this->whereCmp($field, '>=', $min);
        }

        $this->shard_context[$field] = ['~=', [$min, $max]];

        $id = strtr($field, '.', '_');
        $field = '[' . str_replace('.', '].[', $field) . ']';

        $min_key = $id . '_min';
        $max_key = $id . '_max';

        $this->conditions[] = "$field BETWEEN :$min_key AND :$max_key";

        $this->bind[$min_key] = $min;
        $this->bind[$max_key] = $max;

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
            return $this->whereCmp($field, '<', $min);
        }

        $id = strtr($field, '.', '_');
        $field = '[' . str_replace('.', '].[', $field) . ']';

        $min_key = $id . '_min';
        $max_key = $id . '_max';

        $this->conditions[] = "$field NOT BETWEEN :$min_key AND :$max_key";

        $this->bind[$min_key] = $min;
        $this->bind[$max_key] = $max;

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
            $this->shard_context[$field] = $values;

            $id = str_replace('.', '_', $field);
            $field = '[' . str_replace('.', '].[', $field) . ']';

            if (is_int(current($values))) {
                $this->conditions[] = $field . ' IN (' . implode(', ', array_map('intval', $values)) . ')';
            } else {
                $bindKeys = [];
                foreach ($values as $k => $value) {
                    $key = "{$id}_in_{$k}";
                    $bindKeys[] = ":$key";
                    $this->bind[$key] = $value;
                }

                $this->conditions[] = $field . ' IN (' . implode(', ', $bindKeys) . ')';
            }
        } else {
            $this->conditions[] = 'FALSE';
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
        $this->conditions[] = $filter;

        if ($bind !== null) {
            $this->bind = array_merge($this->bind, $bind);
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
                $this->conditions[] = $field . ' NOT IN (' . implode(', ', array_map('intval', $values)) . ')';
            } else {
                $bindKeys = [];
                foreach ($values as $k => $value) {
                    $key = "{$id}_not_in_{$k}";
                    $bindKeys[] = ':' . $key;
                    $this->bind[$key] = $value;
                }

                $this->conditions[] = $field . ' NOT IN (' . implode(', ', $bindKeys) . ')';
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
        $this->conditions[] = 'FIND_IN_SET(:' . $key . ', ' . '[' . str_replace('.', '].[', $field) . '])>0';
        $this->bind[$key] = $value;

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
        $this->conditions[] = 'FIND_IN_SET(:' . $key . ', ' . '[' . str_replace('.', '].[', $field) . '])=0';
        $this->bind[$key] = $value;

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
        if ($value === '') {
            return $this;
        }

        if (is_string($fields) && str_contains($fields, ',')) {
            $fields = preg_split('#[\s,]+#', $fields, -1, PREG_SPLIT_NO_EMPTY);
        }

        if (is_array($fields)) {
            $conditions = [];
            foreach ($fields as $field) {
                $key = strtr($field, '.', '_');
                $conditions[] = '[' . str_replace('.', '].[', $field) . ']' . ' LIKE :' . $key;
                $this->bind[$key] = $value;
            }

            $this->conditions[] = implode(' OR ', $conditions);
        } else {
            $key = strtr($fields, '.', '_');
            $fields = '[' . str_replace('.', '].[', $fields) . ']';
            $this->conditions[] = $fields . ' LIKE :' . $key;
            $this->bind[$key] = $value;
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
        if ($value === '') {
            return $this;
        }

        if (is_string($fields) && str_contains($fields, ',')) {
            $fields = preg_split('#[\s,]+#', $fields, -1, PREG_SPLIT_NO_EMPTY);
        }

        if (is_array($fields)) {
            $conditions = [];
            foreach ($fields as $field) {
                $key = strtr($field, '.', '_');
                $conditions[] = '[' . str_replace('.', '].[', $field) . ']' . ' NOT LIKE :' . $key;
                $this->bind[$key] = $value;
            }

            $this->conditions[] = implode(' AND ', $conditions);
        } else {
            $key = strtr($fields, '.', '_');
            $fields = '[' . str_replace('.', '].[', $fields) . ']';
            $this->conditions[] = $fields . ' NOT LIKE :' . $key;
            $this->bind[$key] = $value;
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
        if ($value !== '') {
            $this->whereLike($fields, $length === null ? $value . '%' : str_pad($value, $length, '_'));
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
        if ($value !== '') {
            $this->whereNotLike($fields, $length === null ? $value . '%' : str_pad($value, $length, '_'));
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
        $mode = (str_contains($flags, 'i') ? '' : 'BINARY ');
        $this->conditions[] = '[' . str_replace('.', '].[', $field) . '] REGEXP ' . $mode . ':' . $key;
        $this->bind[$key] = $regex;

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
        $mode = (str_contains($flags, 'i') ? '' : 'BINARY ');
        $this->conditions[] = '[' . str_replace('.', '].[', $field) . '] NOT REGEXP ' . $mode . ':' . $key;
        $this->bind[$key] = $regex;

        return $this;
    }

    /**
     * @param string $field
     *
     * @return static
     */
    public function whereNull($field)
    {
        $this->conditions[] = '[' . str_replace('.', '].[', $field) . '] IS NULL';

        return $this;
    }

    /**
     * @param string $field
     *
     * @return static
     */
    public function whereNotNull($field)
    {
        $this->conditions[] = '[' . str_replace('.', '].[', $field) . '] IS NOT NULL';

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
            $this->conditions[] = "$id_a=:$id_a OR $id_b=:$id_b";
            $this->bind[$id_a] = $value;
            $this->bind[$id_b] = $value;
        } else {
            $value_a = substr($value, 0, $pos);
            $value_b = substr($value, $pos + 1);

            $condition = "($id_a=:${key_a}_a AND $id_b=:${key_b}_b) OR ($id_a=:${key_a}_b AND $id_b=:${key_b}_a)";
            $this->conditions[] = $condition;

            $this->bind["${key_a}_a"] = $value_a;
            $this->bind["${key_b}_b"] = $value_b;
            $this->bind["${key_a}_b"] = $value_b;
            $this->bind["${key_b}_a"] = $value_a;
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
                $this->having = $having[0];
            } else {
                $items = [];
                foreach ($having as $item) {
                    $items[] = '(' . $item . ')';
                }
                $this->having = implode(' AND ', $items);
            }
        } else {
            $this->having = $having;
        }

        if ($bind) {
            $this->bind = array_merge($this->bind, $bind);
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
        $this->for_update = (bool)$forUpdate;

        return $this;
    }

    /**
     * @param array $group
     *
     * @return string
     */
    protected function buildGroup($group)
    {
        $r = '';
        foreach ($group as $item) {
            if (strpbrk($item, '[(') === false) {
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
        if ($this->sql === null) {
            $shards = $this->getShards();

            $tables = current($shards);
            if (count($tables) !== 1) {
                throw new ShardingTooManyException(__METHOD__);
            }
            $db = $this->getDb(key($shards));
            $this->sql = $this->buildSql($db, $tables[0], $this->joins);
        }

        return $this->sql;
    }

    /**
     * @param array $order
     *
     * @return string
     */
    protected function buildOrder($order)
    {
        $r = '';

        foreach ($order as $field => $v) {
            $type = $v === SORT_ASC ? 'ASC' : 'DESC';

            if (strpbrk($field, '[(') === false) {
                $r .= '[' . str_replace('.', '].[', $field) . '] ' . $type . ', ';
            } else {
                $r .= "$field $type, ";
            }
        }

        return substr($r, 0, -2);
    }

    /**
     * @param array $joins
     *
     * @return string|array
     */
    protected function buildFields($joins)
    {
        if ($this->fields !== null) {
            return $this->fields;
        } elseif ($joins) {
            return '*';
        } else {
            return $this->model ? '[' . implode('], [', $this->model->fields()) . ']' : '*';
        }
    }

    /**
     * @param string $sql
     *
     * @return string
     */
    protected function translateField2Columns($sql)
    {
        if (!($model = $this->model) || !$map = $model->map()) {
            return $sql;
        }

        $pattern = '#\[(' . implode('|', array_keys($map)) . ')]#';

        return preg_replace_callback(
            $pattern, static function ($matches) use ($map) {
            return '[' . $map[$matches[1]] . ']';
        }, $sql
        );
    }

    /**
     * Returns a SQL statement built based on the builder parameters
     *
     * @param \ManaPHP\Data\DbInterface $db
     * @param string                    $table
     * @param array                     $joins
     *
     * @return string
     */
    protected function buildSql($db, $table, $joins)
    {
        $params = [];
        if ($this->distinct) {
            $params['distinct'] = true;
        }

        $params['fields'] = $this->buildFields($joins);

        $table = $db->getPrefix() . $table;
        $table = '[' . str_replace('.', '].[', $table) . ']';
        $alias = $this->alias;
        $params['from'] = $alias ? "$table AS [$alias]" : $table;

        $joinSQL = '';
        foreach ($joins as list($joinTable, $joinCondition, $joinAlias, $joinType)) {
            if ($joinType !== null) {
                $joinSQL .= ' ' . $joinType;
            }

            $joinTable = $db->getPrefix() . $joinTable;
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

        if (count($this->conditions) === 1) {
            $params['where'] = $this->conditions[0];
        } elseif ($this->conditions) {
            $wheres = [];
            foreach ($this->conditions as $v) {
                $wheres[] = stripos($v, ' or ') ? "($v)" : $v;
            }

            $params['where'] = implode(' AND ', $wheres);
        }

        if ($this->group) {
            $params['group'] = $this->buildGroup($this->group);
        }

        if ($this->having !== null) {
            $params['having'] = $this->having;
        }

        if ($this->order) {
            $params['order'] = $this->buildOrder($this->order);
        }

        if ($this->limit !== null) {
            $params['limit'] = $this->limit;
        }

        if ($this->offset !== null) {
            $params['offset'] = $this->offset;
        }

        if ($this->for_update) {
            $params['forUpdate'] = $this->for_update;
        }

        $sql = $db->buildSql($params);
        $sql = $this->translateField2Columns($sql);
        //compatible with other SQL syntax
        $replaces = [];
        foreach ($this->bind as $key => $_) {
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
            return $this->bind[$key] ?? null;
        } else {
            return $this->bind;
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
        $this->bind = $merge ? array_merge($this->bind, $bind) : $bind;

        return $this;
    }

    /**
     * @param string $db
     * @param string $table
     *
     * @return array
     */
    protected function query($db, $table)
    {
        $connection = $this->getDb($db);

        if ($this->joins) {
            $joins = [];
            foreach ($this->joins as $k => $join) {
                $join_table = $join[0];
                if (str_contains($join_table, '\\')) {
                    /** @var \ManaPHP\Data\TableInterface $iTable */
                    $iTable = $join_table::sample();
                    $join_shards = $iTable->getMultipleShards($this->shard_context);
                } else {
                    $db = is_object($this->db) ? '' : (string)$this->db;
                    if ($shard_strategy = $this->shard_strategy) {
                        $join_shards = $shard_strategy($db, $join_table, $this->shard_context);
                    } else {
                        $join_shards = Sharding::multiple($db, $join_table, $this->shard_context);
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

        $this->sql = $this->buildSql($connection, $table, $joins);

        $rows = $connection->fetchAll($this->sql, $this->bind, PDO::FETCH_ASSOC, $this->force_master);

        if ($map = $this->model ? $this->model->map() : []) {
            foreach ($rows as &$row) {
                foreach ($map as $propery => $column) {
                    if (array_key_exists($column, $row)) {
                        $row[$propery] = $row[$column];
                        unset($row[$column]);
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * @return array
     */
    public function execute()
    {
        if (in_array('FALSE', $this->conditions, true)) {
            $this->logger->debug($this->sql, 'db.query.skip');
            return [];
        }

        if ($this->joins) {
            foreach ($this->shard_context as $k => $v) {
                if (($pos = strpos($k, '.')) !== false) {
                    $nk = substr($k, $pos + 1);
                    $this->shard_context[$nk] = $v;
                }
            }
        }

        $shards = $this->getShards();

        $result = [];
        if (count($shards) === 1 && count(current($shards)) === 1) {
            $result = $this->query(key($shards), current($shards)[0]);
        } elseif ($this->order) {
            $copy = clone $this;

            if ($copy->limit) {
                $copy->limit += $copy->offset;
                $copy->offset = 0;
            }

            $valid_times = 0;
            foreach ($shards as $db => $tables) {
                foreach ($tables as $table) {
                    if ($r = $copy->query($db, $table)) {
                        $valid_times++;
                        $result = $result ? array_merge($result, $r) : $r;
                    }
                }
            }

            if ($valid_times > 1) {
                $result = Arr::sort($result, $this->order);
            }

            $result = $this->limit ? array_slice($result, $this->offset, $this->limit) : $result;
        } elseif ($this->limit) {
            foreach ($shards as $db => $tables) {
                foreach ($tables as $table) {
                    if ($r = $this->query($db, $table)) {
                        $result = $result ? array_merge($result, $r) : $r;
                        if (count($result) >= $this->offset + $this->limit) {
                            $result = array_slice($result, (int)$this->offset, $this->limit);
                            return $this->index ? Arr::indexby($result, $this->index) : $result;
                        }
                    }
                }
            }

            $result = $result ? array_slice($result, (int)$this->offset, $this->limit) : [];
        } else {
            foreach ($shards as $db => $tables) {
                foreach ($tables as $table) {
                    if ($r = $this->query($db, $table)) {
                        $result = $result ? array_merge($result, $r) : $r;
                    }
                }
            }
        }

        return $this->index ? Arr::indexby($result, $this->index) : $result;
    }

    /**
     * @param array $expr
     * @param array $group
     *
     * @return string
     */
    protected function buildAggregate($expr, $group)
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
        $this->aggregate = $expr;

        $shards = $this->getShards();

        if (count($shards) === 1 && count(current($shards)) === 1) {
            $this->fields = $this->buildAggregate($expr, $this->group);

            $result = $this->query(key($shards), current($shards)[0]);
        } else {
            if ($this->having) {
                throw new NotSupportedException('sharding not support having');
            }

            $aggs = [];
            foreach ($this->aggregate as $k => $v) {
                if (preg_match('#^\w+#', $v, $match) !== 1) {
                    throw new NotSupportedException($v);
                }

                $agg = strtoupper($match[0]);
                $aggs[$k] = $agg;
                if (in_array($agg, ['COUNT', 'MAX', 'MIN', 'SUM'])) {
                    null;
                } elseif ($agg === 'AVG') {
                    $sum = $k . '_sum';
                    if (!isset($this->aggregate[$sum])) {
                        $this->aggregate[$sum] = 'SUM(' . substr($v, 4);
                    }

                    $count = $k . '_count';
                    if (!isset($this->aggregate[$count])) {
                        $this->aggregate[$count] = 'COUNT(*)';
                    }
                } else {
                    throw new NotSupportedException($v);
                }
            }

            $this->fields = $this->buildAggregate($this->aggregate, $this->group);

            $rows = [];
            foreach ($shards as $db => $tables) {
                foreach ($tables as $table) {
                    if ($r = $this->query($db, $table)) {
                        $rows = $rows ? array_merge($rows, $r) : $r;
                    }
                }
            }

            $result = Arr::aggregate($rows, $aggs, $this->group ?? []);
        }

        if ($this->order) {
            $result = Arr::sort($result, $this->order);
        }

        return $this->index ? Arr::indexby($result, $this->index) : $result;
    }

    /**
     * @param string $field
     *
     * @return int
     */
    public function count($field = '*')
    {
        $copy = clone $this;

        $copy->fields = "COUNT($field) as [row_count]";
        $copy->limit = null;
        $copy->offset = null;
        $copy->order = null;
        $copy->index = null;

        $row_count = 0;
        $shards = $this->getShards();
        foreach ($shards as $db => $tables) {
            foreach ($tables as $table) {
                $result = $copy->query($db, $table);
                $row_count += $this->group ? count($result) : $result[0]['row_count'];
            }
        }

        return $row_count;
    }


    /**
     * @return bool
     */
    public function exists()
    {
        $this->fields = '1 as [stub]';
        $this->limit = 1;
        $this->offset = 0;

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
            foreach ($this->query($db, $table) as $row) {
                $values[] = $row[$field];
            }
        } else {
            foreach ($shards as $db => $tables) {
                foreach ($tables as $table) {
                    foreach ($this->query($db, $table) as $row) {
                        $value = $row[$field];
                        if (!in_array($value, $values, true)) {
                            $values[] = $value;
                        }
                    }
                }
            }

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
     * @param array $fieldValues
     *
     * @return int
     */
    public function update($fieldValues)
    {
        $shards = $this->getShards();

        $affected_count = 0;
        foreach ($shards as $db => $tables) {
            $db = $this->getDb($db);
            foreach ($tables as $table) {
                $affected_count += $db->update($table, $fieldValues, $this->conditions, $this->bind);
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
            $db = $this->getDb($db);
            foreach ($tables as $table) {
                $affected_count += $db->delete($table, $this->conditions, $this->bind);
            }
        }

        return $affected_count;
    }
}
