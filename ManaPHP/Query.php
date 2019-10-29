<?php
namespace ManaPHP;

use ArrayIterator;
use IteratorAggregate;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Model\ShardingTooManyException;
use ManaPHP\Query\NotFoundException;

/**
 * Class Query
 * @package ManaPHP
 * @property-read \ManaPHP\Http\RequestInterface           $request
 * @property-read \ManaPHP\Model\Relation\ManagerInterface $relationsManager
 */
abstract class Query extends Component implements QueryInterface, IteratorAggregate
{
    /**
     * @var string
     */
    protected $_db;

    /**
     * @var string
     */
    protected $_table;

    /**
     * @var array
     */
    protected $_fields;

    /**
     * @var int
     */
    protected $_limit;

    /**
     * @var int
     */
    protected $_offset;

    /**
     * @var bool
     */
    protected $_distinct;

    /**
     * @var \ManaPHP\Model
     */
    protected $_model;

    /**
     * @var bool
     */
    protected $_multiple;

    /**
     * @var array
     */
    protected $_with = [];

    /**
     * @var array
     */
    protected $_order;

    /**
     * @var array
     */
    protected $_group;

    /**
     * @var array
     */
    protected $_equals = [];

    /**
     * @var string|array|callable
     */
    protected $_index;

    /**
     * @var array
     */
    protected $_aggregate;

    /**
     * @var bool
     */
    protected $_force_master = false;

    public function getIterator()
    {
        return new ArrayIterator($this->all());
    }

    public function jsonSerialize()
    {
        return $this->all();
    }

    /**
     * @param string|\ManaPHP\Model $model
     *
     * @return static
     */
    public function setModel($model)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $this->_model = is_string($model) ? $model::sample() : $model;

        return $this;
    }

    /**
     * @return \ManaPHP\Model|null
     */
    public function getModel()
    {
        return $this->_model;
    }

    /**
     * @return array
     */
    public function getUniqueShard()
    {
        $shards = $this->getShards();

        if (count($shards) !== 1) {
            throw new ShardingTooManyException(['too many dbs: `:dbs`', ':dbs' => implode(',', array_keys($shards))]);
        }

        $tables = current($shards);
        if (count($tables) !== 1) {
            throw new ShardingTooManyException(['too many tables: `:tables`', ':tables' => implode(',', $tables)]);
        }

        return [key($shards), $tables[0]];
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
        $this->_distinct = $distinct;

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
            preg_match('#^(\w+)(.*)$#', is_string($k) ? $k : $v, $match);
            $field = $match[1];

            if (is_int($k)) {
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
            } else {
                $this->where([$k => $v]);
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
        if (!$this->_model) {
            throw new MisuseException('use whereDateBetween must provide model');
        }

        if ($min && strpos($min, ':') === false) {
            $min = (int)(is_numeric($min) ? $min : strtotime($min . ' 00:00:00'));
        }
        if ($max && strpos($max, ':') === false) {
            $max = (int)(is_numeric($max) ? $max : strtotime($max . ' 23:59:59'));
        }

        if ($format = $this->_model->getDateFormat($field)) {
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
                        $this->_order[$field] = SORT_ASC;
                    } elseif ($type === 'DESC') {
                        $this->_order[$field] = SORT_DESC;
                    } else {
                        throw new NotSupportedException($orderBy);
                    }
                } else {
                    $this->_order[$order] = SORT_ASC;
                }
            }
        } else {
            foreach ($orderBy as $k => $v) {
                if (is_int($k)) {
                    $this->_order[$v] = SORT_ASC;
                } elseif ($v === SORT_ASC || $v === SORT_DESC) {
                    $this->_order[$k] = $v;
                } elseif ($v === 'ASC' || $v === 'asc') {
                    $this->_order[$k] = SORT_ASC;
                } elseif ($v === 'DESC' || $v === 'desc') {
                    $this->_order[$k] = SORT_DESC;
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

        $this->_index = $indexBy;

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
        $this->_limit = $limit > 0 ? (int)$limit : null;
        $this->_offset = $offset > 0 ? (int)$offset : null;

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
            if (strpos($with, ',') === false) {
                $with = [$with];
            } else {
                $with = (array)preg_split('#[\s,]+#', $with, -1, PREG_SPLIT_NO_EMPTY);
            }
        }

        $with = $this->_with ? array_merge($this->_with, $with) : $with;

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
                $with[$parent_name] = $this->relationsManager->getQuery($this->_model, $parent_name, $parent_value);
            }

            $with[$parent_name]->with(is_int($k) ? [$child_name] : [$child_name => $v]);
            unset($with[$k]);
        }

        $this->_with = $with;

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
     * @param bool $multiple
     *
     * @return static
     */
    public function setFetchType($multiple)
    {
        $this->_multiple = $multiple;

        return $this;
    }

    /**
     * @param bool $asArray
     *
     * @return \ManaPHP\Model[]|\ManaPHP\Model|null|array
     */
    public function fetch($asArray = false)
    {
        if ($asArray) {
            $r = $this->execute();

            if ($r && $this->_with) {
                $r = $this->relationsManager->earlyLoad($this->_model, $r, $this->_with, $asArray);
            }

            return $r;
        } else {
            $modelName = get_class($this->_model);
            $r = $this->execute();
            foreach ($r as $k => $v) {
                $r[$k] = new $modelName($v);
            }

            if ($r && $this->_with) {
                $r = $this->relationsManager->earlyLoad($this->_model, $r, $this->_with, $asArray);
            }

            if ($this->_multiple === false) {
                return $r[0] ?? null;
            } else {
                return $r;
            }
        }
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

        $paginator = $this->_di->get('paginator');
        $paginator->items = $items;
        return $paginator->paginate($count, $this->_limit, (int)($this->_offset / $this->_limit) + 1);
    }

    /**
     * @param bool $forceUseMaster
     *
     * @return static
     */
    public function forceUseMaster($forceUseMaster = true)
    {
        $this->_force_master = $forceUseMaster;

        return $this;
    }

    /**
     * @param string|array $fields
     *
     * @return array|null
     */
    public function first($fields = null)
    {
        $r = $this->select($fields)->limit(1)->fetch(true);
        return $r ? $r[0] : null;
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
        return $r[$field] ?? $default;
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
     * @param mixed    $value
     * @param callable $true_call
     * @param callable $false_call
     *
     * @return static
     */
    public function when($value, $true_call, $false_call = null)
    {
        if ($value) {
            $true_call($this, $value);
        } elseif ($false_call) {
            $false_call($this, $value);
        }

        return $this;
    }

    /**
     * @param string     $field
     * @param string|int $date
     * @param string     $format
     *
     * @return static
     */
    public function whereDate($field, $date, $format = null)
    {
        if ($format === null) {
            if ($this->_model) {
                $format = $this->_model->getDateFormat($field);
            } else {
                $format = is_int($date) ? 'U' : 'Y-m-d H:i:s';
            }
        }

        $ts = is_int($date) ? $date : strtotime($date);

        if ($format === 'U') {
            $min = date('Y-m-d 00:00:00', $ts);
            $max = date('Y-m-d 23:59:59', $ts);
            return $this->whereBetween($field, strtotime($min), strtotime($max));
        } else {
            $min = date(str_replace('H:i:s', '00:00:00', $format), $ts);
            $max = date(str_replace('H:i:s', '23:59:59', $format), $ts);
            return $this->whereBetween($field, $min, $max);
        }
    }

    /**
     * @param string     $field
     * @param string|int $date
     * @param string     $format
     *
     * @return static
     */
    public function whereMonth($field, $date, $format = null)
    {
        if ($format === null) {
            if ($this->_model) {
                $format = $this->_model->getDateFormat($field);
            } else {
                $format = is_int($date) ? 'U' : 'Y-m-d H:i:s';
            }
        }

        if (is_string($date)) {
            $date = str_replace('/', '-', $date);
        }
        $ts = is_int($date) ? $date : strtotime($date);

        if ($format === 'U') {
            $min = date('Y-m-01 00:00:00', $ts);
            $max = date('Y-m-t 23:59:59', $ts);
            return $this->whereBetween($field, strtotime($min), strtotime($max));
        } else {
            $min = date(strtr($format, ['H:i:s' => '00:00:00', 'd' => '01']), $ts);
            $max = date(strtr($format, ['H:i:s' => '23:59:59', 'd' => date('t', $ts)]), $ts);
            return $this->whereBetween($field, $min, $max);
        }
    }
}