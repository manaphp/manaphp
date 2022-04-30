<?php
declare(strict_types=1);

namespace ManaPHP\Data\Mongodb;

use ManaPHP\Data\AbstractQuery;
use ManaPHP\Data\ModelInterface;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\InvalidFormatException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Helper\Arr;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;

/**
 * @property-read \ManaPHP\Data\Mongodb\FactoryInterface $mongodbFactory
 * @property-read \ManaPHP\Data\Model\ManagerInterface   $modelManager
 */
class Query extends AbstractQuery
{
    protected array $types;
    protected array $aliases;
    protected array $filters = [];

    public function __construct(string $connection = 'mongodb')
    {
        $this->connection = $connection;
    }

    public function setModel(ModelInterface $model): static
    {
        $this->model = $model;

        if ($model instanceof Model) {
            $this->setTypes($model->fieldTypes());
        }

        return $this;
    }

    public function setTypes(array $types): static
    {
        $this->types = $types;

        return $this;
    }

    public function values(string $field): array
    {
        list($connection, $source) = $this->getUniqueShard();

        $mongodb = $this->mongodbFactory->get($connection);

        if ($pos = strpos($source, '.')) {
            $db = substr($source, 0, $source);
            $collection = substr($source, $pos + 1);
        } else {
            $db = null;
            $collection = $source;
        }

        $collection = $mongodb->getPrefix() . $collection;
        $cmd = ['distinct' => $collection, 'key' => $field];
        if ($this->filters) {
            $cmd['query'] = $this->buildConditions();
        }

        $r = $mongodb->command($cmd, $db)[0];
        if (!$r['ok']) {
            throw new InvalidFormatException(
                [
                    '`:distinct` distinct for `:collection` collection failed `:code`: `:msg`',
                    'distinct'   => $field,
                    'code'       => $r['code'],
                    'msg'        => $r['errmsg'],
                    'collection' => $source
                ]
            );
        }

        return $this->limit ? array_slice($r['values'], $this->offset, $this->limit) : $r['values'];
    }

    public function select(array $fields): static
    {
        if (!$fields) {
            return $this;
        }

        $this->aliases = [];

        if (isset($fields[count($fields) - 1])) {
            $this->fields = array_fill_keys($fields, 1);
        } else {
            $projection = [];
            foreach ($fields as $k => $v) {
                if (!is_int($k)) {
                    $this->aliases[$k] = $v;
                }
                $projection[$v] = 1;
            }
            $this->fields = $projection;
        }

        $this->fields['_id'] ??= false;

        return $this;
    }

    protected function compileCondExpression(string $expr): ?array
    {
        if (str_contains($expr, ',')) {
            $parts = explode(',', $expr);
            $cond = trim($parts[0]);
            $true = trim($parts[1]);
            $false = isset($parts[2]) ? trim($parts[2]) : 0;

            $true = is_numeric($true) ? (float)$true : '$' . $true;
            $false = is_numeric($false) ? (float)$false : '$' . $false;
        } else {
            $cond = $expr;
            $true = 1;
            $false = 0;
        }

        if (preg_match('#^(.+)\s*([<>=]+)\s*(.+)$#', $cond, $match)) {
            list(, $op1, $op2, $op3) = $match;
            $alg = [
                '='  => '$eq',
                '!=' => '$neq',
                '<>' => '$neq',
                '>'  => '$gt',
                '>=' => '$gte',
                '<'  => '$lt',
                '<=' => '$lte'
            ];
            $normalized_op1 = is_numeric($op1) ? (float)$op1 : '$' . $op1;
            $normalized_op3 = is_numeric($op3) ? (float)$op3 : '$' . $op3;
            return ['$cond' => [[$alg[$op2] => [$normalized_op1, $normalized_op3]], $true, $false]];
        } else {
            return null;
        }
    }

    public function aggregate(array $expr): array
    {
        foreach ($expr as $k => $v) {
            if (is_array($v)) {
                if (isset($v['$count_if'])) {
                    unset($v['$count_if'][0]);
                    $v = ['$sum' => ['$cond' => [$v['$count_if'], 1, 0]]];
                } elseif (isset($v['$sum_if'])) {
                    $field = $v['$sum_if'][0] ?? 1;
                    unset($v['$sum_if'][0]);
                    $v = ['$sum' => ['$cond' => [$v['$sum_if'], is_numeric($field) ? (float)$field : '$' . $field, 0]]];
                } elseif (isset($v['$avg_if'])) {
                    $field = $v['$avg_if'][0] ?? 1;
                    unset($v['$avg_if'][0]);
                    $v = ['$avg' => ['$cond' => [$v['$avg_if'], is_numeric($field) ? (float)$field : '$' . $field, 0]]];
                }
                $this->aggregate[$k] = $v;
                continue;
            }

            if (preg_match('#^(\w+)\((.*)\)$#', $v, $match) !== 1) {
                throw new MisuseException(['`:aggregate` aggregate is invalid.', 'aggregate' => $v]);
            }

            $accumulator = strtolower($match[1]);
            $normalizes = [
                'group_concat' => 'push',
                'std'          => 'stdDevPop',
                'stddev'       => 'stdDevPop',
                'stddev_pop'   => 'stdDevPop',
                'stddev_samp'  => 'stdDevSamp',
                'addtoset'     => 'addToSet',
                'stddevpop'    => 'stdDevPop',
                'stddevsamp'   => 'stdDevSamp'
            ];
            if (isset($normalizes[$accumulator])) {
                $accumulator = $normalizes[$accumulator];
            }
            $operand = $match[2];
            if ($accumulator === 'count') {
                $this->aggregate[$k] = ['$sum' => 1];
            } elseif ($accumulator === 'count_if') {
                if ($cond = $this->compileCondExpression($operand)) {
                    $this->aggregate[$k] = ['$sum' => $cond];
                } else {
                    throw new MisuseException(['unknown COUNT_IF expression: `:expression`', 'expression' => $operand]);
                }
            } elseif ($accumulator === 'sum_if') {
                if ($cond = $this->compileCondExpression($operand)) {
                    $this->aggregate[$k] = ['$sum' => $cond];
                } else {
                    throw new MisuseException(['unknown SUM_IF expression: `:expression`', 'expression' => $operand]);
                }
            } elseif ($accumulator === 'avg_if') {
                if ($cond = $this->compileCondExpression($operand)) {
                    $this->aggregate[$k] = ['$avg' => $cond];
                } else {
                    throw new MisuseException(['unknown AVG_IF expression: `:expression`', 'expression' => $operand]);
                }
            } elseif (in_array(
                $accumulator,
                ['avg', 'first', 'last', 'max', 'min', 'push', 'addToSet', 'stdDevPop', 'stdDevSamp', 'sum'],
                true
            )
            ) {
                if (preg_match('#^[\w.]+$#', $operand) === 1) {
                    $this->aggregate[$k] = ['$' . $accumulator => '$' . $operand];
                } elseif (preg_match('#^([\w.]+)\s*([+\-*/%])\s*([\w.]+)$#', $operand, $match2) === 1) {
                    $operator_map = [
                        '+' => '$add',
                        '-' => '$subtract',
                        '*' => '$multiply',
                        '/' => '$divide',
                        '%' => '$mod'
                    ];
                    $sub_operand = $operator_map[$match2[2]];
                    $sub_operand1 = is_numeric($match2[1]) ? (float)$match2[1] : ('$' . $match2[1]);
                    $sub_operand2 = is_numeric($match2[3]) ? (float)$match2[3] : ('$' . $match2[3]);
                    $this->aggregate[$k] = ['$' . $accumulator => [$sub_operand => [$sub_operand1, $sub_operand2]]];
                } elseif ($cond = $this->compileCondExpression($operand)) {
                    $this->aggregate[$k] = ['$' . $accumulator => $cond];
                } else {
                    throw new MisuseException(['unknown `%s` operand of `%s` aggregate', $operand, $v]);
                }
            } else {
                throw new MisuseException(['unknown `%s` accumulator of `%s` aggregate', $accumulator, $v]);
            }
        }

        return $this->execute();
    }

    public function normalizeValue(string $field, mixed $value): mixed
    {
        if ($value === null || !$this->types) {
            return $value;
        }

        if (!isset($this->types[$field])) {
            throw new MisuseException(['`:field` field type is not defined', 'field' => $field]);
        }

        $type = $this->types[$field];

        if ($type === 'string') {
            return is_string($value) ? $value : (string)$value;
        } elseif ($type === 'int') {
            return is_int($value) ? $value : (int)$value;
        } elseif ($type === 'float') {
            return is_float($value) ? $value : (float)$value;
        } elseif ($type === 'objectid') {
            return is_scalar($value) ? new ObjectId($value) : $value;
        } elseif ($type === 'bool') {
            return is_bool($value) ? $value : (bool)$value;
        } elseif ($type === 'array') {
            return (array)$value;
        } else {
            throw new InvalidValueException(['normalize `:type` type value is not supported', 'type' => $type]);
        }
    }

    public function normalizeValues(string $field, array $values): array
    {
        if (!$this->types) {
            return $values;
        }

        $type = $this->types[$field];

        $map = ['int' => 'intval', 'float' => 'floatval', 'string' => 'strval', 'bool' => 'boolval'];
        if (isset($map[$type])) {
            $values = array_map($map[$type], $values);
        } else {
            foreach ($values as $k => $value) {
                $values[$k] = $this->normalizeValue($field, $value);
            }
        }

        return $values;
    }

    public function whereEq(string $field, mixed $value): static
    {
        $normalizedValue = $this->normalizeValue($field, $value);
        $this->shard_context[$field] = $normalizedValue;

        $this->filters[] = [$field => $normalizedValue];

        return $this;
    }

    public function whereCmp(string $field, string $operator, mixed $value): static
    {
        if (in_array($operator, ['>=', '>', '<', '<='], true)) {
            $this->shard_context[$field] = [$operator, $value];
        }

        if ($operator === '=') {
            return $this->whereEq($field, $value);
        } elseif ($operator === '~=') {
            if ($this->types && !isset($this->types[$field])) {
                $model = $this->model;
                $collection = $model ? $this->modelManager->getTable($model::class) : $this->table;
                throw new InvalidArgumentException(['`%s` field is not exist in `%s` collection', $field, $collection]);
            }

            if (is_scalar($value)) {
                if (is_int($value)) {
                    $this->filters[] = [$field => ['$in' => [(string)$value, $value]]];
                } elseif (is_float($value)) {
                    $this->filters[] = [$field => ['$in' => [(string)$value, $value]]];
                } else {
                    $this->filters[] = [$field => ['$in' => [(string)$value, (int)$value, (float)$value]]];
                }
            } else {
                throw new InvalidValueException(['`%s` operator is not valid: value must be scalar value', $operator]);
            }
        } else {
            $operator_map = ['>' => '$gt', '>=' => '$gte', '<' => '$lt', '<=' => '$lte', '!=' => '$ne', '<>' => '$ne'];
            if (!isset($operator_map[$operator])) {
                throw new InvalidValueException(['unknown `:operator` operator', 'operator' => $operator]);
            }
            $this->filters[] = [$field => [$operator_map[$operator] => $this->normalizeValue($field, $value)]];
        }

        return $this;
    }

    public function whereMod(string $field, int $divisor, int $remainder): static
    {
        $this->filters[] = [$field => ['$mod' => [$divisor, $remainder]]];

        return $this;
    }

    public function whereExpr(string $expr, ?array $bind = null): static
    {
        $this->filters[] = ['$where' => $expr];

        return $this;
    }

    public function whereRaw(string $filter, ?array $bind = null): static
    {
        $this->filters[] = $filter;

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

        $normalized_min = $this->normalizeValue($field, $min);
        $normalized_max = $this->normalizeValue($field, $max);
        $this->filters[] = [$field => ['$gte' => $normalized_min, '$lte' => $normalized_max]];

        return $this;
    }

    public function whereNotBetween(string $field, mixed $min, mixed $max): static
    {
        if ($min === null || $min === '') {
            return $max === null || $max === '' ? $this : $this->whereCmp($field, '>', $max);
        } elseif ($max === null || $max === '') {
            return $this->whereCmp($field, '<', $min);
        }

        $normalized_min = $this->normalizeValue($field, $min);
        $normalized_max = $this->normalizeValue($field, $max);
        $this->filters[] = [$field => ['$not' => ['$gte' => $normalized_min, '$lte' => $normalized_max]]];

        return $this;
    }

    public function whereIn(string $field, array $values): static
    {
        $normalizedValues = $this->normalizeValues($field, $values);
        $this->shard_context[$field] = $normalizedValues;

        $this->filters[] = [$field => ['$in' => $normalizedValues]];

        return $this;
    }

    public function whereNotIn(string $field, array $values): static
    {
        $this->filters[] = [$field => ['$nin' => $this->normalizeValues($field, $values)]];

        return $this;
    }

    public function whereInset(string $field, string $value): static
    {
        return $this->whereRegex($field, '\b' . $value . '\b');
    }

    public function whereNotInset(string $field, string $value): static
    {
        return $this->whereNotRegex($field, '\b' . $value . '\b');
    }

    protected function whereLikeInternal(string|array $fields, string $regex): static
    {
        if ($regex === '') {
            return $this;
        }

        if (is_array($fields)) {
            $or = [];
            foreach ($fields as $v) {
                $or[] = [$v => ['$regex' => $regex, '$options' => 'i']];
            }
            $this->filters[] = ['$or' => $or];
        } else {
            $this->filters[] = [$fields => ['$regex' => $regex, '$options' => 'i']];
        }

        return $this;
    }

    protected function whereNotLikeInternal(string|array $fields, string $regex): static
    {
        if ($regex === '') {
            return $this;
        }

        if (is_array($fields)) {
            $and = [];
            foreach ($fields as $v) {
                $and[] = [$v => ['$not' => new Regex($regex, 'i')]];
            }
            $this->filters[] = ['$and' => $and];
        } else {
            $this->filters[] = [$fields => ['$not' => new Regex($regex, 'i')]];
        }

        return $this;
    }

    public function whereContains(string|array $fields, string $value): static
    {
        return $value === '' ? $this : $this->whereLikeInternal($fields, $value);
    }

    public function whereNotContains(string|array $fields, string $value): static
    {
        return $value === '' ? $this : $this->whereNotLikeInternal($fields, $value);
    }

    public function whereStartsWith(string|array $fields, string $value, ?int $length = null): static
    {
        if ($value === '') {
            return $this;
        }

        if ($length === null) {
            return $this->whereLikeInternal($fields, '^' . $value);
        } else {
            return $this->whereLikeInternal($fields, '^' . str_pad($value, $length, '.') . '$');
        }
    }

    public function whereNotStartsWith(string|array $fields, string $value, ?int $length = null): static
    {
        if ($value === '') {
            return $this;
        }

        if ($length === null) {
            return $this->whereNotLikeInternal($fields, '^' . $value);
        } else {
            return $this->whereNotLikeInternal($fields, '^' . str_pad($value, $length, '.') . '$');
        }
    }

    public function whereEndsWith(string|array $fields, string $value): static
    {
        return $value === '' ? $this : $this->whereLikeInternal($fields, $value . '$');
    }

    public function whereNotEndsWith(string|array $fields, string $value): static
    {
        return $value === '' ? $this : $this->whereNotLikeInternal($fields, $value . '$');
    }

    protected function like2regex(string $like): string
    {
        if ($like === '') {
            return '';
        }

        if ($like[0] !== '%') {
            $like = '^' . $like;
        }

        if ($like[strlen($like) - 1] !== '%') {
            $like .= '$';
        }

        return strtr($like, ['%' => '.*', '_' => '.']);
    }

    public function whereLike(string|array $fields, string $value): static
    {
        return $this->whereLikeInternal($fields, $this->like2regex($value));
    }

    public function whereNotLike(string|array $fields, string $value): static
    {
        return $this->whereNotLikeInternal($fields, $this->like2regex($value));
    }

    public function whereRegex(string $field, string $regex, string $flags = ''): static
    {
        $this->filters[] = [$field => ['$regex' => $regex, '$options' => $flags]];

        return $this;
    }

    public function whereNotRegex(string $field, string $regex, string $flags = ''): static
    {
        $this->filters[] = [$field => ['$not' => new Regex($regex, $flags)]];

        return $this;
    }

    public function whereNull(string $field): static
    {
        $this->filters[] = [$field => ['$type' => 10]];

        return $this;
    }

    public function whereNotNull(string $field): static
    {
        $this->filters[] = [$field => ['$ne' => null]];

        return $this;
    }

    public function where1v1(string $id, string $value): static
    {
        list($id_a, $id_b) = explode(',', $id);

        if (($pos = strpos($value, ',')) === false) {
            $value = $this->normalizeValue($id_a, $value);
            $or = [[$id_a => $value], [$id_b => $value]];
        } else {
            $value_a = $this->normalizeValue($id_a, substr($value, 0, $pos));
            $value_b = $this->normalizeValue($id_b, substr($value, $pos + 1));
            $or = [[$id_a => $value_a, $id_b => $value_b], [$id_a => $value_b, $id_b => $value_a]];
        }
        $this->filters[] = ['$or' => $or];

        return $this;
    }

    public function groupBy(string|array $groupBy): static
    {
        if (is_string($groupBy)) {
            if (str_contains($groupBy, '(')) {
                if (preg_match('#^([\w.]+)\((.*)\)$#', $groupBy, $match) === 1) {
                    $func = strtoupper($match[1]);
                    if ($func === 'SUBSTR') {
                        $parts = explode(',', $match[2]);

                        if ($parts[1] === '0') {
                            throw new MisuseException(['`:group` substr index is 1-based', 'group' => $groupBy]);
                        }
                        $this->group[$parts[0]] = ['$substr' => ['$' . $parts[0], $parts[1] - 1, (int)$parts[2]]];
                    }
                } else {
                    throw new MisuseException(['`:group` group is not supported. ', 'group' => $groupBy]);
                }
            } else {
                foreach (explode(',', str_replace(' ', '', $groupBy)) as $field) {
                    $this->group[$field] = '$' . $field;
                }
            }
        } elseif (is_array($groupBy)) {
            foreach ($groupBy as $k => $v) {
                if (is_int($k)) {
                    $this->group[$v] = '$' . $v;
                } else {
                    $this->group[$k] = $v;
                }
            }
        }

        return $this;
    }

    protected function buildOrder(array $order): array
    {
        $r = [];

        foreach ($order as $field => $type) {
            $r[$field] = $type === SORT_ASC ? 1 : -1;
        }

        return $r;
    }

    protected function buildConditions(): array
    {
        $filters = [];
        foreach ($this->filters as $filter) {
            $key = key($filter);
            $value = current($filter);
            if (isset($filters[$key]) || count($filter) !== 1) {
                $filters = ['$and' => $this->filters];
                break;
            }
            $filters[$key] = $value;
        }

        return $filters;
    }

    public function execute(): array
    {
        list($connection, $collection) = $this->getUniqueShard();

        $mongodb = $this->mongodbFactory->get($connection);

        if (!$this->aggregate) {
            $model = $this->model;

            $options = [];

            if ($this->fields) {
                if (isset($this->fields['*'])) {
                    $options['projection'] = ['_id' => false];
                } else {
                    $options['projection'] = $this->fields;
                }
            } elseif ($model !== null) {
                $options['projection'] = array_fill_keys($model->fields(), 1);
            }

            if (isset($options['projection']) && !isset($options['projection']['_id'])) {
                if ($model !== null) {
                    if ($model->primaryKey() !== '_id') {
                        $options['projection']['_id'] = false;
                    }
                } else {
                    $options['projection']['_id'] = false;
                }
            }

            if ($this->order) {
                $options['sort'] = $this->buildOrder($this->order);
            }

            if ($this->offset !== null) {
                $options['skip'] = $this->offset;
            }

            if ($this->limit !== null) {
                $options['limit'] = $this->limit;
            }

            $r = $mongodb->fetchAll($collection, $this->buildConditions(), $options, !$this->force_master);
            if ($this->aliases) {
                foreach ($r as $k => $v) {
                    foreach ($this->aliases as $ak => $av) {
                        if (isset($v[$av])) {
                            $v[$ak] = $v[$av];
                            unset($v[$av]);
                        }
                    }
                    $r[$k] = $v;
                }
            }
        } else {
            $pipeline = [];
            if ($this->filters) {
                $pipeline[] = ['$match' => ['$and' => $this->filters]];
            }

            $pipeline[] = ['$group' => ['_id' => $this->group] + $this->aggregate];

            if ($this->order) {
                $pipeline[] = ['$sort' => $this->buildOrder($this->order)];
            }

            if ($this->offset !== null) {
                $pipeline[] = ['$skip' => $this->offset];
            }

            if ($this->limit !== null) {
                $pipeline[] = ['$limit' => $this->limit];
            }

            $r = $mongodb->aggregate($collection, $pipeline);

            if ($this->group !== null) {
                foreach ($r as $k => $row) {
                    if ($row['_id'] !== null) {
                        $row += $row['_id'];
                    }
                    unset($row['_id']);
                    $r[$k] = $row;
                }
            }
        }

        return $this->index ? Arr::indexby($r, $this->index) : $r;
    }

    public function count(string $field = '*'): int
    {
        $copy = clone $this;

        $copy->limit = null;
        $copy->offset = null;
        $copy->order = null;
        $copy->aggregate['count'] = ['$sum' => 1];
        $r = $copy->execute();

        return $r ? $r[0]['count'] : 0;
    }

    public function exists(): bool
    {
        return (bool)$this->select(['_id'])->limit(1)->execute();
    }

    public function delete(): int
    {
        $shards = $this->getShards();

        $affected_count = 0;
        foreach ($shards as $connection => $collections) {
            $mongodb = $this->mongodbFactory->get($connection);

            foreach ($collections as $collection) {
                $affected_count += $mongodb->delete($collection, $this->buildConditions());
            }
        }

        return $affected_count;
    }

    public function update(array $fieldValues): int
    {
        $shards = $this->getShards();

        $affected_count = 0;
        foreach ($shards as $connection => $collections) {
            $mongodb = $this->mongodbFactory->get($connection);

            foreach ($collections as $collection) {
                $affected_count += $mongodb->update($collection, $fieldValues, $this->buildConditions());
            }
        }

        return $affected_count;
    }

    public function join(string $table, ?string $condition = null, ?string $alias = null, ?string $type = null): static
    {
        throw new NotSupportedException(__METHOD__);
    }

    public function getSql(): string
    {
        throw new NotSupportedException(__METHOD__);
    }

    public function having(string|array $having, array $bind = []): static
    {
        throw new NotSupportedException(__METHOD__);
    }
}