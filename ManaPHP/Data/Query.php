<?php

namespace ManaPHP\Data;

use ArrayIterator;
use IteratorAggregate;
use ManaPHP\Component;
use ManaPHP\Data\Query\NotFoundException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Helper\Reflection;
use ManaPHP\Helper\Sharding;
use ManaPHP\Helper\Sharding\ShardingTooManyException;

/**
 * @property-read \ManaPHP\Http\RequestInterface          $request
 * @property-read \ManaPHP\Data\Relation\ManagerInterface $relationManager
 */
abstract class Query extends Component implements QueryInterface, IteratorAggregate
{
    /**
     * @var \ManaPHP\Data\DbInterface|string
     */
    protected $db;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var string
     */
    protected $alias;

    /**
     * @var array
     */
    protected $fields;

    /**
     * @var int
     */
    protected $limit;

    /**
     * @var int
     */
    protected $offset;

    /**
     * @var bool
     */
    protected $distinct;

    /**
     * @var \ManaPHP\Data\Model
     */
    protected $model;

    /**
     * @var bool
     */
    protected $multiple;

    /**
     * @var array
     */
    protected $with = [];

    /**
     * @var array
     */
    protected $order;

    /**
     * @var array
     */
    protected $group;

    /**
     * @var string|array|callable
     */
    protected $index;

    /**
     * @var array
     */
    protected $aggregate;

    /**
     * @var bool
     */
    protected $force_master = false;

    /**
     * @var array
     */
    protected $shard_context = [];

    /**
     * @var callable
     */
    protected $shard_strategy;

    /**
     * @var callable
     */
    protected $map;

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->all());
    }

    public function jsonSerialize()
    {
        return $this->all();
    }

    /**
     * @param \ManaPHP\Data\Model $model
     *
     * @return static
     */
    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * @return \ManaPHP\Data\Model|null
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param callable $strategy
     *
     * @return static
     */
    public function shard($strategy)
    {
        $this->shard_strategy = $strategy;

        return $this;
    }

    /**
     * @return array
     */
    public function getShards()
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

    /**
     * @return array
     */
    public function getUniqueShard()
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

    /**
     * @param string $table
     * @param string $alias
     *
     * @return static
     */
    public function from($table, $alias = null)
    {
        if ($table) {
            if (str_contains($table, '\\')) {
                /** @var \ManaPHP\Data\Model $table */
                $sample = $table::sample();

                $this->setModel($sample);
                $table = $sample->table();
            }

            $this->table = $table;
            $this->alias = $alias;
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
        $this->distinct = $distinct;

        return $this;
    }

    /**
     * @param array $filters
     *
     * @return static
     */
    public function search($filters)
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

    /**
     * @param array $filters
     *
     * @return static
     */
    public function where($filters)
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

    /**
     * @param string     $field
     * @param int|string $min
     * @param int|string $max
     *
     * @return static
     */
    public function whereDateBetween($field, $min, $max)
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

    /**
     * @param string|array $groupBy
     *
     * @return static
     */
    public function groupBy($groupBy)
    {
        if (is_string($groupBy)) {
            $this->group = preg_split('#[\s,]+#', $groupBy, -1, PREG_SPLIT_NO_EMPTY);
        } else {
            $this->group = $groupBy;
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

        $this->index = $indexBy;

        return $this;
    }

    /**
     * Sets a LIMIT clause, optionally a offset clause
     *
     * @param int $limit
     * @param int $offset
     *
     * @return static
     */
    public function limit($limit, $offset = null)
    {
        $this->limit = $limit > 0 ? (int)$limit : null;
        $this->offset = $offset > 0 ? (int)$offset : null;

        return $this;
    }

    /**
     * @param string|array $with
     *
     * @return static
     */
    public function with($with)
    {
        if (is_string($with)) {
            if (str_contains($with, ',')) {
                $with = (array)preg_split('#[\s,]+#', $with, -1, PREG_SPLIT_NO_EMPTY);
            } else {
                $with = [$with];
            }
        }

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
            if (!Reflection::isInstanceOf($parent_value, QueryInterface::class)) {
                $with[$parent_name] = $this->relationManager->getQuery($this->model, $parent_name, $parent_value);
            }

            $with[$parent_name]->with(is_int($k) ? [$child_name] : [$child_name => $v]);
            unset($with[$k]);
        }

        $this->with = $with;

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

        $this->limit($size, $page ? ($page - 1) * $size : null);

        return $this;
    }

    /**
     * @param callable $map
     *
     * @return static
     * @noinspection ClassMethodNameMatchesFieldNameInspection
     */
    public function map($map)
    {
        $this->map = $map;

        return $this;
    }

    /**
     * @param bool $multiple
     *
     * @return static
     */
    public function setFetchType($multiple)
    {
        $this->multiple = $multiple;

        return $this;
    }

    /**
     * @return \ManaPHP\Data\Model[]|\ManaPHP\Data\Model|null|array
     */
    public function fetch()
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

    /**
     * @param int $size
     * @param int $page
     *
     * @return \ManaPHP\Data\Paginator
     */
    public function paginate($size = null, $page = null)
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

        /** @var \ManaPHP\Data\Paginator $paginator */
        $paginator = $this->container->make('paginator');
        $paginator->items = $items;
        return $paginator->paginate($count, $this->limit, (int)($this->offset / $this->limit) + 1);
    }

    /**
     * @param bool $forceUseMaster
     *
     * @return static
     */
    public function forceUseMaster($forceUseMaster = true)
    {
        $this->force_master = $forceUseMaster;

        return $this;
    }

    /**
     * @return array|null
     */
    public function first()
    {
        $r = $this->limit(1)->fetch();
        return $r ? $r[0] : null;
    }

    /**
     * @return array
     */
    public function get()
    {
        if (!$r = $this->first()) {
            throw new NotFoundException('record is not exists');
        }

        return $r;
    }

    /**
     * @return array
     */
    public function all()
    {
        return $this->fetch();
    }

    /**
     * @param string $field
     * @param mixed  $default
     *
     * @return mixed
     */
    public function value($field, $default = null)
    {
        $rs = $this->select([$field])->limit(1)->execute();
        return $rs[0][$field] ?? $default;
    }

    /**
     * @param string $field
     *
     * @return int|float|null
     */
    public function sum($field)
    {
        return $this->aggregate(['r' => "SUM($field)"])[0]['r'];
    }

    /**
     * @param string $field
     *
     * @return int|float|null
     */
    public function max($field)
    {
        return $this->aggregate(['r' => "MAX($field)"])[0]['r'];
    }

    /**
     * @param string $field
     *
     * @return int|float|null
     */
    public function min($field)
    {
        return $this->aggregate(['r' => "MIN($field)"])[0]['r'];
    }

    /**
     * @param string $field
     *
     * @return float|null
     */
    public function avg($field)
    {
        return (float)$this->aggregate(['r' => "AVG($field)"])[0]['r'];
    }

    /**
     * @param array $options
     *
     * @return static
     */
    public function options($options)
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

    /**
     * @param callable $call
     *
     * @return static
     */
    public function when($call)
    {
        $call($this);

        return $this;
    }

    /**
     * @param string     $field
     * @param string|int $date
     *
     * @return static
     */
    public function whereDate($field, $date)
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
    public function whereMonth($field, $date)
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
    public function whereYear($field, $date)
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
}