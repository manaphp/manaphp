<?php
declare(strict_types=1);

namespace ManaPHP\Data;

use ArrayIterator;
use IteratorAggregate;
use ManaPHP\Component;
use ManaPHP\Data\Query\NotFoundException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Helper\Sharding;
use ManaPHP\Helper\Sharding\ShardingTooManyException;

/**
 * @property-read \ManaPHP\Http\RequestInterface          $request
 * @property-read \ManaPHP\Data\Relation\ManagerInterface $relationManager
 */
abstract class AbstractQuery extends Component implements QueryInterface, IteratorAggregate
{
    protected mixed $db;
    protected string $table;
    protected string $alias;
    protected string|array $fields;
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected bool $distinct;
    protected ?ModelInterface $model = null;
    protected bool $multiple;
    protected array $with = [];
    protected ?array $order = null;
    protected array $group;
    protected mixed $index;
    protected array $aggregate;
    protected bool $force_master = false;
    protected array $shard_context = [];
    protected mixed $shard_strategy;
    protected mixed $map;

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->all());
    }

    public function jsonSerialize(): array
    {
        return $this->all();
    }

    public function setModel(ModelInterface $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getModel(): ?ModelInterface
    {
        return $this->model;
    }

    public function shard(callable $strategy): static
    {
        $this->shard_strategy = $strategy;

        return $this;
    }

    public function getShards(): array
    {
        if ($model = $this->model ?? null) {
            return $model->getMultipleShards($this->shard_context);
        } else {
            $db = is_object($this->db) ? '' : $this->db;
            $table = $this->table;

            if ($shard_strategy = $this->shard_strategy) {
                return $shard_strategy($db, $table, $this->shard_context);
            } else {
                return Sharding::multiple($db, $table, $this->shard_context);
            }
        }
    }

    public function getUniqueShard(): array
    {
        $shards = $this->getShards();

        if (count($shards) !== 1) {
            throw new ShardingTooManyException(['too many dbs: `:dbs`', 'dbs' => array_keys($shards)]);
        }

        $tables = current($shards);
        if (count($tables) !== 1) {
            throw new ShardingTooManyException(['too many tables: `:tables`', 'tables' => $tables]);
        }

        return [key($shards), $tables[0]];
    }

    public function from(string $table, ?string $alias = null): static
    {
        if ($table) {
            if (str_contains($table, '\\')) {
                /** @var \ManaPHP\Data\ModelInterface $table */
                $sample = $table::sample();

                $this->setModel($sample);
                $table = $sample->table();
            }

            $this->table = $table;
            $this->alias = $alias;
        }

        return $this;
    }

    public function distinct(bool $distinct = true): static
    {
        $this->distinct = $distinct;

        return $this;
    }

    public function search(array $filters): static
    {
        $data = $this->request->get();

        foreach ($filters as $k => $v) {
            if (is_string($k)) {
                $this->where([$k => $v]);
            } else {
                preg_match('#^\w+#', ($pos = strpos($v, '.')) ? substr($v, $pos + 1) : $v, $match);
                $field = $match[0];

                if (!isset($data[$field])) {
                    continue;
                }
                $value = $data[$field];
                if (is_string($value)) {
                    $value = trim($value);
                    if ($value === '') {
                        continue;
                    }
                }
                $this->where([$v => $value]);
            }
        }

        return $this;
    }

    public function where(?array $filters): static
    {
        if ($filters === null) {
            return $this;
        }

        foreach ($filters as $filter => $value) {
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
                    throw new MisuseException(['unknown `:filter` filter', 'operator' => $filter]);
                }
            } elseif (preg_match('#^([\w.]+)([<>=!^$*~,@dm?]*)$#', $filter, $matches) === 1) {
                list(, $field, $operator) = $matches;

                if (str_contains($operator, '?')) {
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
                } elseif ($operator === '@y=') {
                    $this->whereYear($field, $value);
                } else {
                    throw new MisuseException(['unknown `:operator` operator', 'operator' => $operator]);
                }
            } elseif (str_contains($filter, ',') && preg_match('#^[\w,.]+$#', $filter)) {
                $this->where1v1($filter, $value);
            } else {
                throw new MisuseException(['unknown `:filter` filter', 'filter' => $filter]);
            }
        }

        return $this;
    }

    public function whereDateBetween(string $field, mixed $min, mixed $max): static
    {
        if (!$this->model) {
            throw new MisuseException('use whereDateBetween must provide model');
        }

        if ($min && !str_contains($min, ':')) {
            $min = (int)(is_numeric($min) ? $min : strtotime($min . ' 00:00:00'));
        }
        if ($max && !str_contains($max, ':')) {
            $max = (int)(is_numeric($max) ? $max : strtotime($max . ' 23:59:59'));
        }

        if ($format = $this->model->dateFormat(($pos = strpos($field, '.')) ? substr($field, $pos + 1) : $field)) {
            if (is_int($min)) {
                $min = date($format, $min);
            }
            if (is_int($max)) {
                $max = date($format, $max);
            }
        } else {
            if ($min && !is_int($min)) {
                $min = (int)strtotime($min);
            }
            if ($max && !is_int($max)) {
                $max = (int)strtotime($max);
            }
        }

        return $this->whereBetween($field, $min ?: null, $max ?: null);
    }

    public function groupBy(string|array $groupBy): static
    {
        if (is_string($groupBy)) {
            $this->group = preg_split('#[\s,]+#', $groupBy, -1, PREG_SPLIT_NO_EMPTY);
        } else {
            $this->group = $groupBy;
        }

        return $this;
    }

    public function orderBy(string|array $orderBy): static
    {
        if (is_string($orderBy)) {
            foreach (explode(',', $orderBy) as $order) {
                $order = trim($order);
                if ($pos = strrpos($order, ' ')) {
                    $field = substr($order, 0, $pos);
                    $type = strtoupper(substr($order, $pos + 1));
                    if ($type === 'ASC') {
                        $this->order[$field] = SORT_ASC;
                    } elseif ($type === 'DESC') {
                        $this->order[$field] = SORT_DESC;
                    } else {
                        throw new NotSupportedException($orderBy);
                    }
                } else {
                    $this->order[$order] = SORT_ASC;
                }
            }
        } else {
            foreach ($orderBy as $k => $v) {
                if (is_int($k)) {
                    $this->order[$v] = SORT_ASC;
                } elseif ($v === SORT_ASC || $v === SORT_DESC) {
                    $this->order[$k] = $v;
                } elseif ($v === 'ASC' || $v === 'asc') {
                    $this->order[$k] = SORT_ASC;
                } elseif ($v === 'DESC' || $v === 'desc') {
                    $this->order[$k] = SORT_DESC;
                } else {
                    throw new MisuseException(['unknown sort order: `:order`', 'order' => $v]);
                }
            }
        }

        return $this;
    }

    public function indexBy(string|array|callable $indexBy): static
    {
        if (is_array($indexBy)) {
            $this->select([key($indexBy), current($indexBy)]);
        }

        $this->index = $indexBy;

        return $this;
    }

    public function limit(int $limit, ?int $offset = null): static
    {
        $this->limit = $limit > 0 ? $limit : null;
        $this->offset = $offset > 0 ? (int)$offset : null;

        return $this;
    }

    public function with(array $with): static
    {
        $with = $this->with ? array_merge($this->with, $with) : $with;

        foreach ($with as $k => $v) {
            $name = is_string($k) ? $k : $v;
            if (($pos = strpos($name, '.')) === false) {
                continue;
            }
            $parent_name = substr($name, 0, $pos);
            $child_name = substr($name, $pos + 1);
            if (!isset($with[$parent_name])) {
                continue;
            }

            $parent_value = $with[$parent_name];
            if (!$parent_value instanceof QueryInterface) {
                $with[$parent_name] = $this->relationManager->getQuery($this->model, $parent_name, $parent_value);
            }

            $with[$parent_name]->with(is_int($k) ? [$child_name] : [$child_name => $v]);
            unset($with[$k]);
        }

        $this->with = $with;

        return $this;
    }

    public function page(?int $size = null, ?int $page = null): static
    {
        if ($size === null) {
            $size = (int)$this->request->get('size', 10);
        }

        if ($page === null) {
            $page = (int)$this->request->get('page', 1);
        }

        $this->limit($size, $page ? ($page - 1) * $size : null);

        return $this;
    }

    public function map(callable $map): static
    {
        $this->map = $map;

        return $this;
    }

    public function setFetchType(bool $multiple): static
    {
        $this->multiple = $multiple;

        return $this;
    }

    /**
     * @return \ManaPHP\Data\ModelInterface[]|\ManaPHP\Data\ModelInterface|null|array
     */
    public function fetch(): mixed
    {
        $rows = $this->execute();

        if (($model = $this->model) !== null) {
            $modelName = get_class($model);
            foreach ($rows as $k => $v) {
                $rows[$k] = new $modelName($v);
            }
        }

        if ($rows && $this->with) {
            $rows = $this->relationManager->earlyLoad($model, $rows, $this->with);
        }

        if (($map = $this->map) !== null) {
            foreach ($rows as $k => $v) {
                $rows[$k] = $map($v);
            }
        }

        if ($this->multiple === false) {
            return $rows[0] ?? null;
        } else {
            return $rows;
        }
    }

    public function paginate(?int $size = null, ?int $page = null): Paginator
    {
        $this->page($size, $page);

        $items = $this->fetch();

        if ($this->limit === null) {
            $count = count($items);
        } elseif (count($items) % $this->limit === 0) {
            $count = $this->count();
        } else {
            $count = $this->offset + count($items);
        }

        $paginator = $this->container->make(PaginatorInterface::class);
        $paginator->items = $items;
        return $paginator->paginate($count, $this->limit, (int)($this->offset / $this->limit) + 1);
    }

    public function forceUseMaster(bool $forceUseMaster = true): static
    {
        $this->force_master = $forceUseMaster;

        return $this;
    }

    public function first(): ?array
    {
        $r = $this->limit(1)->fetch();
        return $r ? $r[0] : null;
    }

    public function get(): array
    {
        if (!$r = $this->first()) {
            throw new NotFoundException('record is not exists');
        }

        return $r;
    }

    public function all(): array
    {
        return $this->fetch();
    }

    public function value(string $field, mixed $default = null): mixed
    {
        $rs = $this->select([$field])->limit(1)->execute();
        return $rs[0][$field] ?? $default;
    }

    public function sum(string $field): mixed
    {
        return $this->aggregate(['r' => "SUM($field)"])[0]['r'];
    }

    public function max(string $field): mixed
    {
        return $this->aggregate(['r' => "MAX($field)"])[0]['r'];
    }

    public function min(string $field): mixed
    {
        return $this->aggregate(['r' => "MIN($field)"])[0]['r'];
    }

    public function avg(string $field): ?float
    {
        return (float)$this->aggregate(['r' => "AVG($field)"])[0]['r'];
    }

    public function options(array $options): static
    {
        if (!$options) {
            return $this;
        }

        if (isset($options['limit'])) {
            $this->limit($options['limit'], $options['offset'] ?? 0);
        } elseif (isset($options['size'])) {
            $this->page($options['size'], $options['page'] ?? null);
        }

        if (isset($options['distinct'])) {
            $this->distinct($options['distinct']);
        }

        if (isset($options['order'])) {
            $this->orderBy($options['order']);
        }

        if (isset($options['index'])) {
            $this->indexBy($options['index']);
        }

        if (isset($options['with'])) {
            $this->with($options['with']);
        }

        if (isset($options['group'])) {
            $this->groupBy($options['group']);
        }

        return $this;
    }

    public function when(callable $call): static
    {
        $call($this);

        return $this;
    }

    public function whereDate(string $field, int|string $date): static
    {
        if ($this->model) {
            $format = $this->model->dateFormat($field);
        } else {
            $format = is_int($date) ? 'U' : 'Y-m-d H:i:s';
        }

        $ts = is_int($date) ? $date : strtotime($date);

        $min = date('Y-m-d 00:00:00', $ts);
        $max = date('Y-m-d 23:59:59', $ts);

        if ($format === 'U') {
            return $this->whereBetween($field, strtotime($min), strtotime($max));
        } else {
            return $this->whereBetween($field, $min, $max);
        }
    }

    /**
     * @param string     $field
     * @param string|int $date
     *
     * @return static
     */
    public function whereMonth(string $field, int|string $date): static
    {
        if ($this->model) {
            $format = $this->model->dateFormat($field);
        } else {
            $format = is_int($date) ? 'U' : 'Y-m-d H:i:s';
        }

        $ts = is_int($date) ? $date : strtotime($date);

        $min = date('Y-m-01 00:00:00', $ts);
        $max = date('Y-m-t 23:59:59', $ts);

        if ($format === 'U') {
            return $this->whereBetween($field, strtotime($min), strtotime($max));
        } else {
            return $this->whereBetween($field, $min, $max);
        }
    }

    /**
     * @param string     $field
     * @param string|int $date
     *
     * @return static
     */
    public function whereYear(string $field, int|string $date): static
    {
        if ($this->model) {
            $format = $this->model->dateFormat($field);
        } else {
            $format = is_int($date) ? 'U' : 'Y-m-d H:i:s';
        }

        $ts = is_int($date) ? $date : strtotime($date);

        $min = date('Y-01-01 00:00:00', $ts);
        $max = date('Y-12-31 23:59:59', $ts);

        if ($format === 'U') {
            return $this->whereBetween($field, strtotime($min), strtotime($max));
        } else {
            return $this->whereBetween($field, $min, $max);
        }
    }

    public function innerJoin(string $table, ?string $condition = null, ?string $alias = null): static
    {
        return $this->join($table, $condition, $alias, 'INNER');
    }

    public function leftJoin(string $table, ?string $condition = null, ?string $alias = null): static
    {
        return $this->join($table, $condition, $alias, 'LEFT');
    }

    public function rightJoin(string $table, ?string $condition = null, ?string $alias = null): static
    {
        return $this->join($table, $condition, $alias, 'RIGHT');
    }
}