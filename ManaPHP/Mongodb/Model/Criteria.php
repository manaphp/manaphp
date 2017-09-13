<?php
namespace ManaPHP\Mongodb\Model;

use ManaPHP\Component;
use ManaPHP\Di;
use ManaPHP\Mongodb\Model\Criteria\Exception as CriteriaException;

/**
 * Class ManaPHP\Mongodb\Model\Criteria
 *
 * @package ManaPHP\Mongodb\Model
 *
 * @property \ManaPHP\Paginator             $paginator
 * @property \ManaPHP\CacheInterface        $modelsCache
 * @property \ManaPHP\Http\RequestInterface $request
 */
class Criteria extends \ManaPHP\Model\Criteria
{
    /**
     * @var array
     */
    protected $_projection;

    /**
     * @var array
     */
    protected $_aggregate = [];

    /**
     * @var string
     */
    protected $_modelName;

    /**
     * @var array
     */
    protected $_filters = [];

    /**
     * @var string
     */
    protected $_order;

    /**
     * @var string|callable
     */
    protected $_index;

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
     * @var int|array
     */
    protected $_cacheOptions;

    /**
     * @var array
     */
    protected $_group;

    /**
     * @var bool
     */
    protected $_forceUseMaster = false;

    /**
     * Criteria constructor.
     *
     * @param string       $modelName
     * @param string|array $fields
     */
    public function __construct($modelName, $fields = null)
    {
        $this->_modelName = $modelName;

        if ($fields !== null) {
            $this->select($fields);
        }
        $this->_dependencyInjector = Di::getDefault();
    }

    /**
     * Sets SELECT DISTINCT / SELECT ALL flag
     *
     * @param string $field
     *
     * @return array
     * @throws \ManaPHP\Mongodb\Model\Criteria\Exception
     */
    public function distinctField($field)
    {
        /**
         * @var \ManaPHP\ModelInterface $modelName
         */
        $modelName = $this->_modelName;
        $source = $modelName::getSource();

        /**
         * @var \ManaPHP\MongodbInterface $db
         */
        $db = $this->_dependencyInjector->getShared($modelName::getDb());

        $cmd = ['distinct' => $source, 'key' => $field];
        if (count($this->_filters) !== 0) {
            $cmd['query'] = ['$and' => $this->_filters];
        }

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $cursor = $db->command($cmd);
        $r = $cursor->toArray()[0];
        if (!$r['ok']) {
            throw new CriteriaException('`:distinct` distinct for `:collection` collection failed `:code`: `:msg`',
                ['distinct' => $field, 'code' => $r['code'], 'msg' => $r['errmsg'], 'collection' => $source]);
        }

        return $r['values'];
    }

    /**
     * @param string|array $fields
     *
     * @return static
     */
    public function select($fields)
    {
        if (!is_array($fields)) {
            $fields = explode(',', str_replace(['[', ']', "\t", ' ', "\r", "\n"], '', $fields));
        }

        $this->_projection = array_fill_keys($fields, 1);

        return $this;
    }

    /**
     * @param array $expr
     *
     * @return array
     * @throws \ManaPHP\Mongodb\Model\Criteria\Exception
     */
    public function aggregate($expr)
    {
        foreach ($expr as $k => $v) {
            if (is_array($v)) {
                $this->_aggregate[$k] = $v;
                continue;
            }

            if (preg_match('#^(\w+)\((.*)\)$#', $v, $match) !== 1) {
                throw new CriteriaException('`:aggregate` aggregate is invalid.', ['aggregate' => $v]);
            }

            $accumulator = strtolower($match[1]);
            $operand = $match[2];
            if ($accumulator === 'count') {
                $this->_aggregate[$k] = ['$sum' => 1];
            } elseif ($accumulator === 'sum' || $accumulator === 'avg' || $accumulator === 'max' || $accumulator === 'min') {
                if (preg_match('#^[\w\.]+$#', $operand) === 1) {
                    $this->_aggregate[$k] = ['$' . $accumulator => '$' . $operand];
                } elseif (preg_match('#^([\w\.]+)\s*([\+\-\*/%])\s*([\w\.]+)$#', $operand, $match2) === 1) {
                    $operator_map = ['+' => '$add', '-' => '$subtract', '*' => '$multiply', '/' => '$divide', '%' => '$mod'];
                    $sub_operand = $operator_map[$match2[2]];
                    $sub_operand1 = is_numeric($match2[1]) ? (double)$match2[1] : ('$' . $match2[1]);
                    $sub_operand2 = is_numeric($match2[3]) ? (double)$match2[3] : ('$' . $match2[3]);
                    $this->_aggregate[$k] = ['$' . $accumulator => [$sub_operand => [$sub_operand1, $sub_operand2]]];
                } else {
                    throw new CriteriaException('unknown `:operand` operand of `:aggregate` aggregate', ['operand' => $operand, 'aggregate' => $v]);
                }
            } else {
                throw new CriteriaException('unknown `:accumulator` accumulator of `:aggregate` aggregate',
                    ['accumulator' => $accumulator, 'aggregate' => $v]);
            }
        }

        return $this->execute();
    }

    /**
     * Appends a condition to the current conditions using a AND operator
     *
     *<code>
     *    $builder->andWhere('name = "Peter"');
     *    $builder->andWhere('name = :name: AND id > :id:', array('name' => 'Peter', 'id' => 100));
     *</code>
     *
     * @param string|array           $filter
     * @param int|float|string|array $value
     *
     * @return static
     * @throws \ManaPHP\Mongodb\Model\Criteria\Exception
     */
    public function where($filter, $value = null)
    {
        if ($filter === null) {
            return $this;
        } elseif (is_array($filter)) {
            /** @noinspection ForeachSourceInspection */
            foreach ($filter as $k => $v) {
                $this->where($k, $v);
            }
        } elseif (is_array($value)) {
            if (strpos($filter, '~=')) {
                if (count($value) !== 2 || !isset($value[0], $value[1])) {
                    throw new CriteriaException('`:filter` filter is valid: value is not a two elements array', ['filter' => $filter]);
                }
                $this->whereBetween(substr($filter, 0, -2), $value[0], $value[1]);
            } elseif (isset($value[0]) || count($value) === 0) {
                if (strpos($filter, '!=') || strpos($filter, '<>')) {
                    $this->whereNotIn(substr($filter, 0, -2), $value);
                } else {
                    $this->whereIn($filter, $value);
                }
            } else {
                $this->_filters[] = [$filter => $value];
            }
        } elseif (preg_match('#^([\w\.]+)\s*([<>=!^$*~]*)$#', $filter, $matches) === 1) {
            list(, $field, $operator) = $matches;
            if ($operator === '') {
                $operator = '=';
            }

            if ($operator === '^=') {
                $this->whereStartsWith($field, $value);
            } elseif ($operator === '$=') {
                $this->whereEndsWith($field, $value);
            } elseif ($operator === '*=') {
                $this->whereContains($field, $value);
            } elseif ($operator === '~=') {
                $this->_filters[] = [$field => ['$regex' => $value, '$options' => 'i']];
            } else {
                $operator_map = ['=' => '$eq', '>' => '$gt', '>=' => '$gte', '<' => '$lt', '<=' => '$lte', '!=' => '$ne', '<>' => '$ne'];
                if (isset($operator_map[$operator])) {
                    $this->_filters[] = [$field => [$operator_map[$operator] => $value]];
                } else {
                    throw new CriteriaException('unknown `:where` where filter', ['where' => $filter]);
                }
            }
        } else {
            throw new CriteriaException('unknown mongodb criteria `filter` filter', ['filter' => $filter]);
        }

        return $this;
    }

    /**
     * @param array $filter
     *
     * @return static
     */
    public function whereRaw($filter)
    {
        $this->_filters[] = $filter;

        return $this;
    }

    /**
     * Appends a BETWEEN condition to the current conditions
     *
     *<code>
     *    $builder->betweenWhere('price', 100.25, 200.50);
     *</code>
     *
     * @param string           $expr
     * @param int|float|string $min
     * @param int|float|string $max
     *
     * @return static
     */
    public function whereBetween($expr, $min, $max)
    {
        $this->_filters[] = [$expr => ['$gte' => $min, '$lte' => $max]];

        return $this;
    }

    /**
     * Appends a NOT BETWEEN condition to the current conditions
     *
     *<code>
     *    $builder->notBetweenWhere('price', 100.25, 200.50);
     *</code>
     *
     * @param string           $expr
     * @param int|float|string $min
     * @param int|float|string $max
     *
     * @return static
     */
    public function whereNotBetween($expr, $min, $max)
    {
        $this->_filters[] = ['$or' => [[$expr => ['$lt' => $min]], [$expr => ['$gt' => $max]]]];

        return $this;
    }

    /**
     * Appends an IN condition to the current conditions
     *
     *<code>
     *    $builder->inWhere('id', [1, 2, 3]);
     *</code>
     *
     * @param string                           $expr
     * @param array|\ManaPHP\Db\QueryInterface $values
     *
     * @return static
     */
    public function whereIn($expr, $values)
    {
        $this->_filters[] = [$expr => ['$in' => $values]];

        return $this;
    }

    /**
     * Appends a NOT IN condition to the current conditions
     *
     *<code>
     *    $builder->notInWhere('id', [1, 2, 3]);
     *</code>
     *
     * @param string                           $expr
     * @param array|\ManaPHP\Db\QueryInterface $values
     *
     * @return static
     */
    public function whereNotIn($expr, $values)
    {
        $this->_filters[] = [$expr => ['$nin' => $values]];

        return $this;
    }

    /**
     * @param string|array $expr
     * @param string       $like
     *
     * @return static
     */
    protected function _whereLike($expr, $like)
    {
        if (is_array($expr)) {
            $or = [];
            /** @noinspection ForeachSourceInspection */
            foreach ($expr as $v) {
                $or[] = [$v => ['$regex' => $like]];
            }
            $this->_filters[] = ['$or' => $or];
        } else {
            $this->_filters[] = [$expr => ['$regex' => $like]];
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
        return $this->_whereLike($expr, $value);
    }

    /**
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     */
    public function whereStartsWith($expr, $value)
    {
        return $this->_whereLike($expr, '^' . $value);
    }

    /**
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     */
    public function whereEndsWith($expr, $value)
    {
        return $this->_whereLike($expr, $value . '$');
    }

    /**
     * @param string $expr
     *
     * @return static
     */
    public function whereNull($expr)
    {
        $this->_filters[] = [$expr => ['$type' => 10]];

        return $this;
    }

    /**
     * @param string $expr
     *
     * @return static
     */
    public function whereNotNull($expr)
    {
        $this->_filters[] = [$expr => ['$ne' => null]];

        return $this;
    }

    /**
     * Sets a ORDER BY condition clause
     *
     *<code>
     *    $builder->orderBy('Robots.name');
     *    $builder->orderBy(array('1', 'Robots.name'));
     *</code>
     *
     * @param string|array $orderBy
     *
     * @return static
     * @throws \ManaPHP\Mongodb\Model\Criteria\Exception
     */
    public function orderBy($orderBy)
    {
        if (is_string($orderBy)) {
            foreach (explode(',', $orderBy) as $item) {
                if (preg_match('#^\s*([\w\.]+)(\s+asc|\s+desc)?$#i', $item, $match) !== 1) {
                    throw new CriteriaException('unknown `:order` order by for `:model` model', ['order' => $orderBy, 'model' => $this->_modelName]);
                }
                $this->_order[$match[1]] = (!isset($match[2]) || strtoupper(ltrim($match[2])) === 'ASC') ? 1 : -1;
            }
        } else {
            /** @noinspection ForeachSourceInspection */
            foreach ($orderBy as $field => $value) {
                if ((is_int($value) && $value === SORT_ASC) || (is_string($value) && strtoupper($value) === 'ASC')) {
                    $this->_order[$field] = 1;
                } else {
                    $this->_order[$field] = -1;
                }
            }
        }

        return $this;
    }

    /**
     * Sets a LIMIT clause, optionally a offset clause
     *
     *<code>
     *    $builder->limit(100);
     *    $builder->limit(100, 20);
     *</code>
     *
     * @param int $limit
     * @param int $offset
     *
     * @return static
     */
    public function limit($limit, $offset = null)
    {
        if ($limit > 0) {
            $this->_limit = (int)$limit;
        }

        if ($offset > 0) {
            $this->_offset = (int)$offset;
        }

        return $this;
    }

    /**
     * @param int $size
     * @param int $page
     *
     * @return static
     */
    public function page($size, $page = null)
    {
        if ($page === null && $this->request->has('page')) {
            $page = $this->request->get('page', 'int');
        }

        $this->limit($size, $page ? ($page - 1) * $size : null);

        return $this;
    }

    /**
     * Sets a GROUP BY clause
     *
     *<code>
     *    $builder->groupBy(array('Robots.name'));
     *</code>
     *
     * @param string|array $groupBy
     *
     * @return static
     * @throws \ManaPHP\Mongodb\Model\Criteria\Exception
     */
    public function groupBy($groupBy)
    {
        if (is_string($groupBy)) {
            if (strpos($groupBy, '(') !== false) {
                if (preg_match('#^([\w\.]+)\((.*)\)$#', $groupBy, $match) === 1) {
                    $func = strtoupper($match[1]);
                    if ($func === 'SUBSTR') {
                        $parts = explode(',', $match[2]);

                        if ($parts[1] === '0') {
                            throw new CriteriaException('`:group` substr index is 1-based', ['group' => $groupBy]);
                        }
                        $this->_group[$parts[0]] = ['$substr' => ['$' . $parts[0], $parts[1] - 1, (int)$parts[2]]];
                    }
                } else {
                    throw new CriteriaException('`:group` group is not supported. ', ['group' => $groupBy]);
                }
            } else {
                foreach (explode(',', str_replace(' ', '', $groupBy)) as $field) {
                    $this->_group[$field] = '$' . $field;
                }
            }
        } else {
            $this->_group = $groupBy;
        }

        return $this;
    }

    /**
     * @param callable|string $indexBy
     *
     * @return static
     */
    public function indexBy($indexBy)
    {
        $this->_index = $indexBy;

        return $this;
    }

    /**
     * @param array|int $options
     *
     * @return static
     */
    public function cache($options)
    {
        $this->_cacheOptions = $options;

        return $this;
    }

    /**
     * @return array
     */
    protected function _execute()
    {
        /**
         * @var \ManaPHP\ModelInterface $modelName
         */
        $modelName = $this->_modelName;
        $source = $modelName::getSource();
        /**
         * @var \ManaPHP\MongodbInterface $db
         */
        $db = Di::getDefault()->getShared($modelName::getDb());
        if (count($this->_aggregate) === 0) {
            $options = [];

            if ($this->_projection !== null) {
                $options['projection'] = $this->_projection;
            }

            if ($this->_order !== null) {
                $options['sort'] = $this->_order;
            }

            if ($this->_offset !== null) {
                $options['skip'] = $this->_offset;
            }

            if ($this->_limit !== null) {
                $options['limit'] = $this->_limit;
            }

            $r = $db->query($source, $this->_filters ? ['$and' => $this->_filters] : [], $options, !$this->_forceUseMaster);
            if ($this->_index === null) {
                return $r;
            } else {
                $index = $this->_index;
                $rows = [];
                foreach ($r as $v) {
                    $rows[is_scalar($index) ? $v[$index] : $index($v)] = $v;
                }

                return $rows;
            }
        } else {
            $pipeline = [];
            if (count($this->_filters) !== 0) {
                $pipeline[] = ['$match' => ['$and' => $this->_filters]];
            }

            $pipeline[] = ['$group' => ['_id' => $this->_group] + $this->_aggregate];

            if ($this->_order !== null) {
                $pipeline[] = ['$sort' => $this->_order];
            }

            if ($this->_offset !== null) {
                $pipeline[] = ['$skip' => $this->_offset];
            }

            if ($this->_limit !== null) {
                $pipeline[] = ['$limit' => $this->_limit];
            }

            $r = $db->pipeline($source, $pipeline);

            if ($this->_group !== null) {
                foreach ($r as $k => $v) {
                    if ($v['_id'] !== null) {
                        $v += $v['_id'];
                    }
                    unset($v['_id']);
                    $r[$k] = $v;
                }
            }
            if ($this->_index === null) {
                return $r;
            } else {
                $index = $this->_index;
                $rows = [];
                foreach ($r as $v) {
                    $rows[is_scalar($index) ? $v[$index] : $index($v)] = $v;
                }

                return $rows;
            }
        }
    }

    /**
     * @return array
     */
    public function execute()
    {
        if ($this->_cacheOptions !== null) {
            $cacheOptions = $this->_getCacheOptions();
            $data = $this->modelsCache->get($cacheOptions['key']);
            if ($data !== false) {
                return json_decode($data, true)['items'];
            }
        }

        $items = $this->_execute();

        if (isset($cacheOptions)) {
            $this->modelsCache->set($cacheOptions['key'], json_encode(['time' => date('Y-m-d H:i:s'), 'items' => $items]), $cacheOptions['ttl']);
        }

        return $items;
    }

    /**
     * @return int
     */
    protected function _getTotalRows()
    {
        $this->_limit = null;
        $this->_offset = null;
        $this->_order = null;
        $this->_aggregate['count'] = ['$sum' => 1];
        $r = $this->_execute();
        return $r[0]['count'];
    }

    /**
     * @param int $size
     * @param int $page
     *
     * @return \ManaPHP\PaginatorInterface
     * @throws \ManaPHP\Mongodb\Model\Criteria\Exception
     * @throws \ManaPHP\Paginator\Exception
     */
    public function paginate($size, $page = null)
    {
        if ($page === null && $this->request->has('page')) {
            $page = $this->request->get('page', 'int');
        }
        $this->page($size, $page);

        do {
            if ($this->_cacheOptions !== null) {
                $cacheOptions = $this->_getCacheOptions();

                if (($result = $this->modelsCache->get($cacheOptions['key'])) !== false) {
                    $result = json_decode($result, true);

                    $count = $result['count'];
                    $items = $result['items'];
                    break;
                }
            }

            $copy = clone $this;
            $items = $this->fetchAll();

            if ($this->_limit === null) {
                $count = count($items);
            } else {
                if (count($items) % $this->_limit === 0) {
                    $count = $copy->_getTotalRows();
                } else {
                    $count = $this->_offset + count($items);
                }
            }

            if (isset($cacheOptions)) {
                $this->modelsCache->set($cacheOptions['key'],
                    json_encode(['time' => date('Y-m-d H:i:s'), 'count' => $count, 'items' => $items], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    $cacheOptions['ttl']);
            }

        } while (false);

        $this->paginator->items = $items;
        return $this->paginator->paginate($count, $size, $page);
    }

    /**
     *
     * @return array
     */
    protected function _getCacheOptions()
    {
        $cacheOptions = is_array($this->_cacheOptions) ? $this->_cacheOptions : ['ttl' => $this->_cacheOptions];

        if (!isset($cacheOptions['key'])) {
            $data = [];
            foreach (get_object_vars($this) as $k => $v) {
                if ($v !== null && !$v instanceof Component) {
                    $data[$k] = $v;
                }
            }
            $cacheOptions['key'] = md5(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return $cacheOptions;
    }

    /**
     * @param bool $forceUseMaster
     *
     * @return static
     */
    public function forceUseMaster($forceUseMaster = true)
    {
        $this->_forceUseMaster = $forceUseMaster;

        return $this;
    }

    /**
     * @return \ManaPHP\Mongodb\Model|false
     */
    public function fetchOne()
    {
        $r = $this->limit(1)->fetchAll();
        return isset($r[0]) ? $r[0] : false;
    }

    /**
     *
     * @return \ManaPHP\Mongodb\Model[]
     */
    public function fetchAll()
    {
        $rows = [];

        foreach ($this->execute() as $k => $document) {
            $rows[$k] = new $this->_modelName($document);

        }

        return $rows;
    }

    /**
     * @return array
     */
    public function asArray()
    {
        return $this->execute();
    }

    /**
     * @return bool
     */
    public function exists()
    {
        return $this->select(['_id'])->fetchOne() !== false;
    }

    public function delete()
    {
        /**
         * @var \ManaPHP\ModelInterface $modelName
         */
        $modelName = $this->_modelName;
        if (($db = $modelName::getDb($this)) === false) {
            throw new CriteriaException('`:model` model db sharding for update failed',
                ['model' => $modelName, 'context' => $this]);
        }

        if (($source = $modelName::getSource($this)) === false) {
            throw new CriteriaException('`:model` model table sharding for update failed',
                ['model' => $modelName, 'context' => $this]);
        }

        return $this->_dependencyInjector->getShared($db)->delete($source, $this->_filters ? ['$and' => $this->_filters] : []);
    }

    public function update($fieldValues)
    {
        /**
         * @var \ManaPHP\ModelInterface $modelName
         */
        $modelName = $this->_modelName;
        if (($db = $modelName::getDb($this)) === false) {
            throw new CriteriaException('`:model` model db sharding for update failed',
                ['model' => $modelName, 'context' => $this]);
        }

        if (($source = $modelName::getSource($this)) === false) {
            throw new CriteriaException('`:model` model table sharding for update failed',
                ['model' => $modelName, 'context' => $this]);
        }

        return $this->_dependencyInjector->getShared($db)->update($source, $fieldValues, $this->_filters ? ['$and' => $this->_filters] : []);
    }
}
