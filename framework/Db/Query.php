<?php
declare(strict_types=1);

namespace ManaPHP\Db;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Helper\Arr;
use ManaPHP\Helper\Sharding;
use ManaPHP\Helper\Sharding\ShardingTooManyException;
use ManaPHP\Query\AbstractQuery;
use PDO;
use Psr\Log\LoggerInterface;

class Query extends AbstractQuery
{
    #[Autowired] protected LoggerInterface $logger;
    #[Autowired] protected DbConnectorInterface $connector;

    protected array $joins = [];
    protected array $conditions = [];
    protected ?string $having = null;
    protected bool $for_update = false;
    protected array $bind = [];
    protected ?string $sql = null;

    public function __construct(string $connection = 'default')
    {
        $this->connection = $connection;
    }

    public function select(array $fields): static
    {
        if (!$fields) {
            return $this;
        }

        $r = '';
        foreach ($fields as $k => $v) {
            if (strpbrk($v, '[(') === false) {
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

    public function join(string $table, ?string $condition = null, ?string $alias = null, ?string $type = null): static
    {
        if (strpbrk($condition, '[(') === false) {
            $condition = (string)preg_replace('#\w+#', '[\\0]', $condition);
        }

        $this->joins[] = [$table, $condition, $alias, $type];

        return $this;
    }

    public function whereEq(string $field, mixed $value): static
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

    public function whereCmp(string $field, string $operator, mixed $value): static
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
            throw new MisuseException(['unknown `{operator}` operator', 'operator' => $operator]);
        }

        return $this;
    }

    public function whereMod(string $field, int $divisor, int $remainder): static
    {
        $this->conditions[] = "$field%$divisor=$remainder";

        return $this;
    }

    public function whereExpr(string $expr, ?array $bind = null): static
    {
        $this->conditions[] = $expr;

        if (is_array($bind)) {
            $this->bind = array_merge($this->bind, $bind);
        }

        return $this;
    }

    public function whereBetween(string $field, mixed $min, mixed $max): static
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

    public function whereNotBetween(string $field, mixed $min, mixed $max): static
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

    public function whereIn(string $field, array $values): static
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
                    $key = "{$id}_in_$k";
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

    public function whereRaw(string $filter, ?array $bind = null): static
    {
        $this->conditions[] = $filter;

        if ($bind !== null) {
            $this->bind = array_merge($this->bind, $bind);
        }

        return $this;
    }

    public function whereNotIn(string $field, array $values): static
    {
        if ($values) {
            $id = str_replace('.', '_', $field);
            $field = '[' . str_replace('.', '].[', $field) . ']';

            if (is_int(current($values))) {
                $this->conditions[] = $field . ' NOT IN (' . implode(', ', array_map('intval', $values)) . ')';
            } else {
                $bindKeys = [];
                foreach ($values as $k => $value) {
                    $key = "{$id}_not_in_$k";
                    $bindKeys[] = ':' . $key;
                    $this->bind[$key] = $value;
                }

                $this->conditions[] = $field . ' NOT IN (' . implode(', ', $bindKeys) . ')';
            }
        }

        return $this;
    }

    public function whereInset(string $field, string $value): static
    {
        $key = strtr($field, '.', '_');
        $this->conditions[] = 'FIND_IN_SET(:' . $key . ', ' . '[' . str_replace('.', '].[', $field) . '])>0';
        $this->bind[$key] = $value;

        return $this;
    }

    public function whereNotInset(string $field, string $value): static
    {
        $key = strtr($field, '.', '_');
        $this->conditions[] = 'FIND_IN_SET(:' . $key . ', ' . '[' . str_replace('.', '].[', $field) . '])=0';
        $this->bind[$key] = $value;

        return $this;
    }

    public function whereLike(string|array $fields, string $value): static
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

    public function whereNotLike(string|array $fields, string $value): static
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

    public function whereContains(string|array $fields, string $value): static
    {
        return $value === '' ? $this : $this->whereLike($fields, '%' . $value . '%');
    }

    public function whereNotContains(string|array $fields, string $value): static
    {
        return $value === '' ? $this : $this->whereNotLike($fields, '%' . $value . '%');
    }

    public function whereStartsWith(string|array $fields, string $value, ?int $length = null): static
    {
        if ($value !== '') {
            $this->whereLike($fields, $length === null ? $value . '%' : str_pad($value, $length, '_'));
        }

        return $this;
    }

    public function whereNotStartsWith(string|array $fields, string $value, ?int $length = null): static
    {
        if ($value !== '') {
            $this->whereNotLike($fields, $length === null ? $value . '%' : str_pad($value, $length, '_'));
        }

        return $this;
    }

    public function whereEndsWith(string|array $fields, string $value): static
    {
        return $value === '' ? $this : $this->whereLike($fields, '%' . $value);
    }

    public function whereNotEndsWith(string|array $fields, string $value): static
    {
        return $value === '' ? $this : $this->whereNotLike($fields, '%' . $value);
    }

    public function whereRegex(string $field, string $regex, string $flags = ''): static
    {
        $key = strtr($field, '.', '_');
        $mode = (str_contains($flags, 'i') ? '' : 'BINARY ');
        $this->conditions[] = '[' . str_replace('.', '].[', $field) . '] REGEXP ' . $mode . ':' . $key;
        $this->bind[$key] = $regex;

        return $this;
    }

    public function whereNotRegex(string $field, string $regex, string $flags = ''): static
    {
        $key = strtr($field, '.', '_');
        $mode = (str_contains($flags, 'i') ? '' : 'BINARY ');
        $this->conditions[] = '[' . str_replace('.', '].[', $field) . '] NOT REGEXP ' . $mode . ':' . $key;
        $this->bind[$key] = $regex;

        return $this;
    }

    public function whereNull(string $field): static
    {
        $this->conditions[] = '[' . str_replace('.', '].[', $field) . '] IS NULL';

        return $this;
    }

    public function whereNotNull(string $field): static
    {
        $this->conditions[] = '[' . str_replace('.', '].[', $field) . '] IS NOT NULL';

        return $this;
    }

    public function where1v1(string $id, string $value): static
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

            $condition = "($id_a=:{$key_a}_a AND $id_b=:{$key_b}_b) OR ($id_a=:{$key_a}_b AND $id_b=:{$key_b}_a)";
            $this->conditions[] = $condition;

            $this->bind["{$key_a}_a"] = $value_a;
            $this->bind["{$key_b}_b"] = $value_b;
            $this->bind["{$key_a}_b"] = $value_b;
            $this->bind["{$key_b}_a"] = $value_a;
        }

        return $this;
    }

    public function having(string|array $having, array $bind = []): static
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

    public function forUpdate(bool $forUpdate = true): static
    {
        $this->for_update = $forUpdate;

        return $this;
    }

    protected function buildGroup(array $group): string
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

    public function getSql(): string
    {
        if ($this->sql === null) {
            $shards = $this->getShards();

            $tables = current($shards);
            if (count($tables) !== 1) {
                throw new ShardingTooManyException(__METHOD__);
            }

            $connection = key($shards);
            $db = $this->connector->get($connection);

            $this->sql = $this->buildSql($db, $tables[0], $this->joins);
        }

        return $this->sql;
    }

    protected function buildOrder(array $order): string
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

    protected function buildFields(array $joins): string|array
    {
        if ($this->fields !== null) {
            return $this->fields;
        } elseif ($joins) {
            return '*';
        } else {
            return $this->model ? '[' . implode('], [', $this->models->getFields($this->model)) . ']' : '*';
        }
    }

    protected function translateField2Columns(string $sql): string
    {
        if (($model = $this->model) === null) {
            return $sql;
        }

        if (($columnMap = $this->models->getColumnMap($model)) === []) {
            return $sql;
        }

        $pattern = '#\[(' . implode('|', array_keys($columnMap)) . ')]#';

        return preg_replace_callback($pattern, static fn($matches) => '[' . $columnMap[$matches[1]] . ']', $sql);
    }

    protected function buildSql(DbInterface $db, string $table, array $joins): string
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

        if ($this->limit > 0) {
            $params['limit'] = $this->limit;
        }

        if ($this->offset > 0) {
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

    public function getBind(?string $key = null): ?array
    {
        if ($key !== null) {
            return $this->bind[$key] ?? null;
        } else {
            return $this->bind;
        }
    }

    public function setBind(array $bind, bool $merge = true): static
    {
        $this->bind = $merge ? array_merge($this->bind, $bind) : $bind;

        return $this;
    }

    protected function query(string $connection, string $table): array
    {
        $db = $this->connector->get($connection);

        $joins = [];
        if ($this->joins) {
            foreach ($this->joins as $join) {
                $join_table = $join[0];
                if (str_contains($join_table, '\\')) {
                    $join_shards = $this->sharding->getMultipleShards($join_table, $this->shard_context);
                } else {
                    $connection = $this->connection;
                    if ($shard_strategy = $this->shard_strategy) {
                        $join_shards = $shard_strategy($connection, $join_table, $this->shard_context);
                    } else {
                        $join_shards = Sharding::multiple($connection, $join_table, $this->shard_context);
                    }
                }

                if (!isset($join_shards[$connection])) {
                    throw new NotSupportedException('');
                }

                $join_tables = $join_shards[$connection];
                if (count($join_tables) > 1) {
                    throw new NotSupportedException('');
                }

                $join[0] = $join_tables[0];
                $joins[] = $join;
            }
        }

        $this->sql = $this->buildSql($db, $table, $joins);

        $rows = $db->fetchAll($this->sql, $this->bind, PDO::FETCH_ASSOC, $this->force_master);

        $model = $this->model;
        if ($columnMap = $model ? $this->models->getColumnMap($model) : []) {
            foreach ($rows as &$row) {
                foreach ($columnMap as $propery => $column) {
                    if (array_key_exists($column, $row)) {
                        $row[$propery] = $row[$column];
                        unset($row[$column]);
                    }
                }
            }
        }

        return $rows;
    }

    public function execute(): array
    {
        if (in_array('FALSE', $this->conditions, true)) {
            $this->logger->debug('SQL: {0}', [$this->sql, 'category' => 'db.query.skip']);
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
            foreach ($shards as $connection => $tables) {
                foreach ($tables as $table) {
                    if ($r = $copy->query($connection, $table)) {
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
            foreach ($shards as $connection => $tables) {
                foreach ($tables as $table) {
                    if ($r = $this->query($connection, $table)) {
                        $result = $result ? array_merge($result, $r) : $r;
                        if (count($result) >= $this->offset + $this->limit) {
                            $result = array_slice($result, $this->offset, $this->limit);
                            return $this->index ? Arr::indexby($result, $this->index) : $result;
                        }
                    }
                }
            }

            $result = $result ? array_slice($result, $this->offset, $this->limit) : [];
        } else {
            foreach ($shards as $connection => $tables) {
                foreach ($tables as $table) {
                    if ($r = $this->query($connection, $table)) {
                        $result = $result ? array_merge($result, $r) : $r;
                    }
                }
            }
        }

        return $this->index ? Arr::indexby($result, $this->index) : $result;
    }

    protected function buildAggregate(array $expr, array $group): string
    {
        $fields = '';

        foreach ($expr as $k => $v) {
            if (is_int($k)) {
                $fields .= '[' . $v . '], ';
            } else {
                if (preg_match('#^(\w+)\((\w+)\)$#', $v, $matches) === 1) {
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

    public function aggregate(array $expr): array
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
                if (in_array($agg, ['COUNT', 'MAX', 'MIN', 'SUM'], true)) {
                    null;
                } elseif ($agg === 'AVG') {
                    $sum = $k . '_sum';
                    $this->aggregate[$sum] ??= 'SUM(' . substr($v, 4);

                    $count = $k . '_count';
                    $this->aggregate[$count] ??= 'COUNT(*)';
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

    public function count(string $field = '*'): int
    {
        $copy = clone $this;

        $copy->fields = "COUNT($field) as [row_count]";
        $copy->limit = null;
        $copy->offset = 0;
        $copy->order = null;
        $copy->index = null;

        $row_count = 0;
        $shards = $this->getShards();
        foreach ($shards as $connection => $tables) {
            foreach ($tables as $table) {
                $result = $copy->query($connection, $table);
                $row_count += $this->group ? count($result) : $result[0]['row_count'];
            }
        }

        return $row_count;
    }

    public function exists(): bool
    {
        $this->fields = '1 as [stub]';
        $this->limit = 1;
        $this->offset = 0;

        $rs = $this->execute();

        return isset($rs[0]);
    }

    /**
     * @return string
     * @noinspection PhpUnusedLocalVariableInspection
     */
    public function __toString()
    {
        try {
            return $this->getSql();
        } catch (\Exception $e) {
            return '';
        }
    }

    public function values(string $field): array
    {
        $this->distinct()->select([$field]);

        $values = [];

        $shards = $this->getShards();
        if (count($shards) === 1 && count(current($shards)) === 1) {
            $connection = key($shards);
            $table = current($shards)[0];
            foreach ($this->query($connection, $table) as $row) {
                $values[] = $row[$field];
            }
        } else {
            foreach ($shards as $connection => $tables) {
                foreach ($tables as $table) {
                    foreach ($this->query($connection, $table) as $row) {
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

    public function update(array $fieldValues): int
    {
        $shards = $this->getShards();

        $affected_count = 0;
        foreach ($shards as $connection => $tables) {
            $db = $this->connector->get($connection);

            foreach ($tables as $table) {
                $affected_count += $db->update($table, $fieldValues, $this->conditions, $this->bind);
            }
        }

        return $affected_count;
    }

    public function delete(): int
    {
        $shards = $this->getShards();

        $affected_count = 0;
        foreach ($shards as $connection => $tables) {
            $db = $this->connector->get($connection);

            foreach ($tables as $table) {
                $affected_count += $db->delete($table, $this->conditions, $this->bind);
            }
        }

        return $affected_count;
    }
}
