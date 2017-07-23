<?php

namespace ManaPHP\Mvc\Model;

use ManaPHP\Component;
use ManaPHP\Mvc\Model\QueryBuilder\Exception as QueryBuilderException;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Mvc\Model\QueryBuilder
 *
 * @package queryBuilder
 *
 * @property \ManaPHP\Cache\AdapterInterface     $modelsCache
 * @property \ManaPHP\Paginator                  $paginator
 * @property \ManaPHP\Mvc\Model\ManagerInterface $modelsManager
 * @property \ManaPHP\Mvc\DispatcherInterface    $dispatcher
 */
class QueryBuilder extends Component implements QueryBuilderInterface
{
    /**
     * @var string
     */
    protected $_columns;

    /**
     * @var array
     */
    protected $_models = [];

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
     * @var int
     */
    protected $_limit = 0;

    /**
     * @var int
     */
    protected $_offset = 0;

    /**
     * @var bool
     */
    protected $_forUpdate;

    /**
     * @var array
     */
    protected $_bind = [];

    /**
     * @var bool
     */
    protected $_distinct;

    /**
     * @var int
     */
    protected static $_hiddenParamNumber = 0;

    /**
     * @var \ManaPHP\Mvc\Model
     */
    protected static $_modelInstance;

    /**
     * @var array
     */
    protected $_union = [];

    /**
     * @var string
     */
    protected $_sql;

    /**
     * @var int|array
     */
    protected $_cacheOptions;

    /**
     * \ManaPHP\Mvc\Model\Query\Builder constructor
     *
     *<code>
     * $params = array(
     *    'models'     => array('Users'),
     *    'columns'    => array('id', 'name', 'status'),
     *    'conditions' => array(
     *        array(
     *            "created > :min: AND created < :max:",
     *            array("min" => '2013-01-01',   'max' => '2015-01-01'),
     *            array("min" => PDO::PARAM_STR, 'max' => PDO::PARAM_STR),
     *        ),
     *    ),
     *    // or 'conditions' => "created > '2013-01-01' AND created < '2015-01-01'",
     *    'group'      => array('id', 'name'),
     *    'having'     => "name = 'lily'",
     *    'order'      => array('name', 'id'),
     *    'limit'      => 20,
     *    'offset'     => 20,
     *    // or 'limit' => array(20, 20),
     *);
     *$queryBuilder = new \ManaPHP\Mvc\Model\Query\Builder($params);
     *</code>
     *
     * @param array|string $params
     */
    public function __construct($params = null)
    {
        if ($params === null) {
            $params = [];
        } elseif (is_string($params)) {
            $params = [$params];
        }

        if (isset($params[0])) {
            $this->_conditions = $params[0];
        } elseif (isset($params['conditions'])) {
            $this->_conditions = $params['conditions'];
        } else {
            $this->_conditions = $params;
            $params = [];
        }

        if (isset($params[1])) {
            $params['bind'] = $params[1];
            unset($params[1]);
        }

        if (isset($params['bind'])) {
            $this->_bind = array_merge($this->_bind, $params['bind']);
        }

        if (isset($params['distinct'])) {
            $this->distinct($params['distinct']);
        }

        if (isset($params['models'])) {
            $this->_models = (array)$params['models'];
        }

        if (isset($params['columns'])) {
            $this->columns($params['columns']);
        }

        if (isset($params['joins'])) {
            $this->_joins = $params['joins'];
        }

        if (isset($params['group'])) {
            $this->groupBy($params['group']);
        }

        if (isset($params['having'])) {
            $this->having($params['having']);
        }

        if (isset($params['order'])) {
            $this->orderBy($params['order']);
        }

        if (isset($params['limit'])) {
            $this->limit($params['limit'], isset($params['offset']) ? $params['offset'] : 0);
        }

        if (isset($params['offset'])) {
            $this->_offset = (int)$params['offset'];
        }

        if (isset($params['for_update'])) {
            $this->forUpdate($params['for_update']);
        }
    }

    /**
     * Sets SELECT DISTINCT / SELECT ALL flag
     *
     * @param bool $distinct
     *
     * @return static
     */
    public function distinct($distinct)
    {
        $this->_distinct = $distinct;

        return $this;
    }

    /**
     * Sets the columns to be queried
     *
     *<code>
     *    $builder->columns(array('id', 'name'));
     *</code>
     *
     * @param string $columns
     *
     * @return static
     */
    public function columns($columns)
    {
        $this->_columns = preg_replace('/(\s+)/', ' ', $columns);

        return $this;
    }

    /**
     * Sets the models who makes part of the query
     *
     *<code>
     *    $builder->from('Robots');
     *    $builder->from(array('Robots', 'RobotsParts'));
     *</code>
     *
     * @param string|array $models
     *
     * @return static
     */
    public function from($models)
    {
        $this->_models = [$models];

        return $this;
    }

    /**
     * Add a model to take part of the query
     *
     *<code>
     *    $builder->addFrom('Robots', 'r');
     *</code>
     *
     * @param string|\ManaPHP\Mvc\Model\QueryBuilderInterface $model
     * @param string                                          $alias
     *
     * @return static
     */
    public function addFrom($model, $alias = null)
    {
        if (is_string($alias)) {
            $this->_models[$alias] = $model;
        } else {
            $this->_models[] = $model;
        }

        return $this;
    }

    /**
     * Adds a join to the query
     *
     *<code>
     *    $builder->join('Robots');
     *    $builder->join('Robots', 'r.id = RobotsParts.robots_id');
     *    $builder->join('Robots', 'r.id = RobotsParts.robots_id', 'r');
     *    $builder->join('Robots', 'r.id = RobotsParts.robots_id', 'r', 'LEFT');
     *</code>
     *
     * @param string|\ManaPHP\Mvc\Model\QueryBuilderInterface $model
     * @param string                                          $conditions
     * @param string                                          $alias
     * @param string                                          $type
     *
     * @return static
     */
    public function join($model, $conditions = null, $alias = null, $type = null)
    {
        $this->_joins[] = [$model, $conditions, $alias, $type];

        return $this;
    }

    /**
     * Adds a INNER join to the query
     *
     *<code>
     *    $builder->innerJoin('Robots');
     *    $builder->innerJoin('Robots', 'r.id = RobotsParts.robots_id');
     *    $builder->innerJoin('Robots', 'r.id = RobotsParts.robots_id', 'r');
     *</code>
     *
     * @param string|\ManaPHP\Mvc\Model\QueryBuilderInterface $model
     * @param string                                          $conditions
     * @param string                                          $alias
     *
     * @return static
     */
    public function innerJoin($model, $conditions = null, $alias = null)
    {
        $this->_joins[] = [$model, $conditions, $alias, 'INNER'];

        return $this;
    }

    /**
     * Adds a LEFT join to the query
     *
     *<code>
     *    $builder->leftJoin('Robots', 'r.id = RobotsParts.robots_id', 'r');
     *</code>
     *
     * @param string|\ManaPHP\Mvc\Model\QueryBuilderInterface $model
     * @param string                                          $conditions
     * @param string                                          $alias
     *
     * @return static
     */
    public function leftJoin($model, $conditions = null, $alias = null)
    {
        $this->_joins[] = [$model, $conditions, $alias, 'LEFT'];

        return $this;
    }

    /**
     * Adds a RIGHT join to the query
     *
     *<code>
     *    $builder->rightJoin('Robots', 'r.id = RobotsParts.robots_id', 'r');
     *</code>
     *
     * @param string|\ManaPHP\Mvc\Model\QueryBuilderInterface $model
     * @param string                                          $conditions
     * @param string                                          $alias
     *
     * @return static
     */
    public function rightJoin($model, $conditions = null, $alias = null)
    {
        $this->_joins[] = [$model, $conditions, $alias, 'RIGHT'];

        return $this;
    }

    /**
     * Sets the query conditions
     *
     *<code>
     *    $builder->where('name = "Peter"');
     *    $builder->where('name = :name: AND id > :id:', array('name' => 'Peter', 'id' => 100));
     *</code>
     *
     * @param string                 $conditions
     * @param int|float|string|array $bind
     *
     * @return static
     */
    public function where($conditions, $bind = [])
    {
        return $this->andWhere($conditions, $bind);
    }

    /**
     * Appends a condition to the current conditions using a AND operator
     *
     *<code>
     *    $builder->andWhere('name = "Peter"');
     *    $builder->andWhere('name = :name: AND id > :id:', array('name' => 'Peter', 'id' => 100));
     *</code>
     *
     * @param string                 $conditions
     * @param int|float|string|array $bind
     *
     * @return static
     */
    public function andWhere($conditions, $bind = [])
    {
        if (is_scalar($bind)) {
            $conditions = trim($conditions);

            if (!Text::contains($conditions, ' ')) {
                $conditions .= ' =';
            }

            $parts = explode(' ', $conditions, 2);
            $conditions = preg_replace('#[a-z_][a-z0-9_]*#i', '[\\0]', $parts[0]) . ' ' . $parts[1];
            $column = str_replace('.', '_', $parts[0]);
            /** @noinspection CascadeStringReplacementInspection */
            $from = ['`', '[', ']'];
            $column = str_replace($from, '', $column);

            $conditions = $conditions . ' :' . $column;
            $bind = [$column => $bind];
        }

        $this->_conditions[] = $conditions;

        $this->_bind = array_merge($this->_bind, $bind);

        return $this;
    }

    /**
     * Appends a BETWEEN condition to the current conditions
     *
     *<code>
     *    $builder->betweenWhere('price', 100.25, 200.50);
     *</code>
     *
     * @param string    $expr
     * @param int|float $min
     * @param int|float $max
     *
     * @return static
     */
    public function betweenWhere($expr, $min, $max)
    {
        $minKey = '_between_min_' . self::$_hiddenParamNumber;
        $maxKey = '_between_max_' . self::$_hiddenParamNumber;

        self::$_hiddenParamNumber++;

        $bind = [$minKey => $min, $maxKey => $max];
        $this->andWhere("$expr BETWEEN :$minKey AND :$maxKey", $bind);

        return $this;
    }

    /**
     * Appends a NOT BETWEEN condition to the current conditions
     *
     *<code>
     *    $builder->notBetweenWhere('price', 100.25, 200.50);
     *</code>
     *
     * @param string    $expr
     * @param int|float $min
     * @param int|float $max
     *
     * @return static
     */
    public function notBetweenWhere($expr, $min, $max)
    {
        $minKey = '_not_between_min_' . self::$_hiddenParamNumber;
        $maxKey = '_not_between_max_' . self::$_hiddenParamNumber;

        self::$_hiddenParamNumber++;

        $bind = [$minKey => $min, $maxKey => $max];
        $this->andWhere("$expr NOT BETWEEN :$minKey AND :$maxKey", $bind);

        return $this;
    }

    /**
     * Appends an IN condition to the current conditions
     *
     *<code>
     *    $builder->inWhere('id', [1, 2, 3]);
     *</code>
     *
     * @param string                                         $expr
     * @param array|\ManaPHP\Mvc\Model\QueryBuilderInterface $values
     *
     * @return static
     */
    public function inWhere($expr, $values)
    {
        if ($values instanceof $this) {
            $this->andWhere($expr . ' IN (' . $values->getSql() . ')');
            $this->_bind = array_merge($this->_bind, $values->getBind());
        } else {
            if (count($values) === 0) {
                $this->andWhere('1=2');

                return $this;
            }

            $bind = [];
            $bindKeys = [];

            /** @noinspection ForeachSourceInspection */
            foreach ($values as $k => $value) {
                $key = '_in_' . self::$_hiddenParamNumber . '_' . $k;
                $bindKeys[] = ":$key";
                $bind[$key] = $value;
            }

            self::$_hiddenParamNumber++;

            $this->andWhere($expr . ' IN (' . implode(', ', $bindKeys) . ')', $bind);
        }

        return $this;
    }

    /**
     * Appends a NOT IN condition to the current conditions
     *
     *<code>
     *    $builder->notInWhere('id', [1, 2, 3]);
     *</code>
     *
     * @param string                                         $expr
     * @param array|\ManaPHP\Mvc\Model\QueryBuilderInterface $values
     *
     * @return static
     */
    public function notInWhere($expr, $values)
    {
        if ($values instanceof $this) {
            $this->andWhere($expr . ' NOT IN (' . $values->getSql() . ')');
            $this->_bind = array_merge($this->_bind, $values->getBind());
        } else {
            if (count($values) === 0) {
                return $this;
            }

            $bind = [];
            $bindKeys = [];

            /** @noinspection ForeachSourceInspection */
            foreach ($values as $k => $value) {
                $key = '_not_in_' . self::$_hiddenParamNumber . '_' . $k;
                $bindKeys[] = ':' . $key;
                $bind[$key] = $value;
            }

            self::$_hiddenParamNumber++;

            $this->andWhere($expr . ' NOT IN (' . implode(', ', $bindKeys) . ')', $bind);
        }
        return $this;
    }

    /**
     * @param string|array $expr
     * @param string       $like
     *
     * @return static
     */
    public function likeWhere($expr, $like)
    {
        if (is_array($expr)) {
            $conditions = [];
            $bind = [];
            /** @noinspection ForeachSourceInspection */
            foreach ($expr as $column) {
                $key = str_replace('.', '_', $column);
                $conditions[] = $column . ' LIKE :' . $key;
                $bind[$key] = $like;
            }

            return $this->andWhere(implode(' OR ', $conditions), $bind);
        } else {
            $key = str_replace('.', '_', $expr);
            return $this->andWhere($expr . ' LIKE :' . $key, [$key => $like]);
        }
    }

    /**
     * Sets a ORDER BY condition clause
     *
     *<code>
     *    $builder->orderBy('Robots.name');
     *    $builder->orderBy(array('1', 'Robots.name'));
     *</code>
     *
     * @param string $orderBy
     *
     * @return static
     */
    public function orderBy($orderBy)
    {
        if (preg_match('#^[a-z0-9_.\s]*$#i', $orderBy) === 1) {
            $r = '';
            foreach (explode(',', $orderBy) as $item) {
                $parts = explode(' ', trim($item));
                if (count($parts) === 1) {
                    $by = trim($parts[0]);
                    $type = 'ASC';
                } elseif (count($parts) === 2) {
                    $by = trim($parts[0]);
                    $type = strtoupper($parts[1]);
                } else {
                    $r = $orderBy;
                    break;
                }

                if (preg_match('#^[a-z_][a-z0-9_.]*#i', $by) === 1) {
                    $r .= preg_replace('#[a-z_][a-z0-9_]*#i', '[\\0]', $by) . ' ' . $type . ', ';
                }
            }

            $this->_order = substr($r, 0, -2);
        } else {
            $this->_order = $orderBy;
        }

        return $this;
    }

    /**
     * Sets a HAVING condition clause. You need to escape SQL reserved words using [ and ] delimiters
     *
     *<code>
     *    $builder->having('SUM(Robots.price) > 0');
     *</code>
     *
     * @param string $having
     * @param array  $bind
     *
     * @return static
     */
    public function having($having, $bind = [])
    {
        $this->_having = $having;
        $this->_bind = array_merge($this->_bind, $bind);

        return $this;
    }

    /**
     * Sets a FOR UPDATE clause
     *
     *<code>
     *    $builder->forUpdate(true);
     *</code>
     *
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
    public function limit($limit, $offset = 0)
    {
        $this->_limit = (int)$limit;
        $this->_offset = (int)$offset;

        return $this;
    }

    /**
     * @param int $size
     * @param int $current
     *
     * @return static
     */
    public function page($size, $current = 1)
    {
        $current = (int)max(1, $current);

        $this->_limit = (int)$size;
        $this->_offset = (int)($current - 1) * $size;

        return $this;
    }

    /**
     * Sets a GROUP BY clause
     *
     *<code>
     *    $builder->groupBy(array('Robots.name'));
     *</code>
     *
     * @param string $groupBy
     *
     * @return static
     */
    public function groupBy($groupBy)
    {
        $r = '';
        foreach (explode(',', $groupBy) as $item) {
            $parts = explode(' ', trim($item));
            if (count($parts) === 1) {
                $by = trim($parts[0]);
            } else {
                $r = $groupBy;
                break;
            }

            if (preg_match('#^[a-z_][a-z0-9_.]*#i', $by) === 1) {
                $r .= preg_replace('#[a-z_][a-z0-9_]*#i', '[\\0]', $by) . ', ';
            }
        }

        $this->_group = substr($r, 0, -2);

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
     * @return string
     * @throws \ManaPHP\Mvc\Model\QueryBuilder\Exception
     */
    protected function _getUnionSql()
    {
        $unions = [];

        /**
         * @var \ManaPHP\Mvc\Model\QueryBuilder $builder
         */
        /** @noinspection ForeachSourceInspection */
        foreach ($this->_union['builders'] as $builder) {
            $unions[] = '(' . $builder->getSql() . ')';

            /** @noinspection SlowArrayOperationsInLoopInspection */
            $this->_bind = array_merge($this->_bind, $builder->getBind());
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
        if ($this->_limit !== 0) {
            $params['limit'] = $this->_limit;
        }

        if ($this->_offset !== 0) {
            $params['offset'] = $this->_offset;
        }

        $sql .= self::$_modelInstance->getReadConnection()->buildSql($params);

        $this->_models[] = $builder->getModels()[0];

        return $sql;
    }

    /**
     * @return string
     * @throws \ManaPHP\Mvc\Model\QueryBuilder\Exception
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
     * @throws \ManaPHP\Mvc\Model\QueryBuilder\Exception
     */
    protected function _buildSql()
    {
        if (count($this->_union) !== 0) {
            return $this->_getUnionSql();
        }

        if (count($this->_models) === 0) {
            throw new QueryBuilderException('at least one model is required to build the query'/**m09d10c2135a4585fa*/);
        }

        $params = [];
        if ($this->_distinct) {
            $params['distinct'] = true;
        }

        if ($this->_columns !== null) {
            $columns = $this->_columns;
        } else {
            $columns = '';
            $selectedColumns = [];
            foreach ($this->_models as $alias => $model) {
                $selectedColumns[] = '[' . (is_int($alias) ? $model : $alias) . '].*';
            }
            $columns .= implode(', ', $selectedColumns);
        }
        $params['columns'] = $columns;

        $selectedModels = [];

        foreach ($this->_models as $alias => $model) {
            if ($model instanceof $this) {
                if (is_int($alias)) {
                    throw new QueryBuilderException('if using SubQuery, you must assign an alias for it'/**m0e5f4aa93dc102dde*/);
                }

                $selectedModels[] = '(' . $model->getSql() . ') AS [' . $alias . ']';
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $this->_bind = array_merge($this->_bind, $model->getBind());
            } else {
                if (is_string($alias)) {
                    $selectedModels[] = '[' . $model . '] AS [' . $alias . ']';
                } else {
                    $selectedModels[] = '[' . $model . ']';
                }
            }
        }

        $params['from'] = implode(', ', $selectedModels);

        $joinSQL = '';
        /** @noinspection ForeachSourceInspection */
        foreach ($this->_joins as $join) {
            list($joinModel, $joinCondition, $joinAlias, $joinType) = $join;

            if ($joinAlias !== null) {
                $this->_models[$joinAlias] = $joinModel;
            } else {
                $this->_models[] = $joinModel;
            }

            if ($joinType !== null) {
                $joinSQL .= ' ' . $joinType;
            }

            if ($joinModel instanceof $this) {
                $joinSQL .= ' JOIN (' . $joinModel->getSql() . ')';
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $this->_bind = array_merge($this->_bind, $joinModel->getBind());
                if ($joinAlias === null) {
                    throw new QueryBuilderException('if using SubQuery, you must assign an alias for it'/**m0a80f96a41e1596cb*/);
                }
            } else {
                $joinSQL .= ' JOIN [' . $joinModel . ']';
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

        if (is_string($this->_conditions)) {
            $this->_conditions = [$this->_conditions];
        }

        /** @noinspection ForeachSourceInspection */
        foreach ($this->_conditions as $k => $v) {
            if ($v === '') {
                continue;
            }

            if (is_int($k)) {
                $wheres[] = Text::contains($v, ' or ', true) ? "($v)" : $v;
            } else {
                $wheres[] = "[$k]=:$k";
                $this->_bind[$k] = $v;
            }
        }

        if (count($wheres) !== 0) {
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

        if ($this->_limit !== 0) {
            $params['limit'] = $this->_limit;
        }

        if ($this->_offset !== 0) {
            $params['offset'] = $this->_offset;
        }

        if ($this->_forUpdate) {
            $params['forUpdate'] = $this->_forUpdate;
        }

        if (self::$_modelInstance === null) {
            foreach ($this->_models as $model) {
                if (is_string($model)) {
                    self::$_modelInstance = new $model;
                    break;
                }
            }
        }

        $sql = self::$_modelInstance->getReadConnection()->buildSql($params);
        //compatible with other SQL syntax
        $replaces = [];
        foreach ($this->_bind as $key => $_) {
            $replaces[':' . $key . ':'] = ':' . $key;
        }

        $sql = strtr($sql, $replaces);

        foreach ($this->_models as $model) {
            if (!$model instanceof $this) {
                $source = $this->modelsManager->getModelSource($model);
                if (strpos($source, '.')) {
                    $source = '[' . implode('].[', explode('.', $source)) . ']';
                } else {
                    $source = '[' . $source . ']';
                }
                $sql = str_replace('[' . $model . ']', $source, $sql);
            }
        }

        return $sql;
    }

    public function getBind()
    {
        return $this->_bind;
    }

    /**
     * Set default bind parameters
     *
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
    public function getModels()
    {
        return $this->_models;
    }

    /**
     * @param int|array $cacheOptions
     *
     * @return array
     * @throws \ManaPHP\Mvc\Model\QueryBuilder\Exception
     */
    protected function _getCacheOptions($cacheOptions)
    {
        $_cacheOptions = is_array($cacheOptions) ? $cacheOptions : ['ttl' => $cacheOptions];

        if (isset($this->_models[0]) && count($this->_models) === 1) {
            $modelName = $this->_models[0];
            $prefix = '/' . $this->dispatcher->getModuleName() . '/Models/' . substr($modelName, strrpos($modelName, '\\') + 1);
        } else {
            $prefix = '/' . $this->dispatcher->getModuleName() . '/Queries/' . $this->dispatcher->getControllerName();
        }

        if (isset($_cacheOptions['key'])) {
            if ($_cacheOptions['key'][0] === '/') {
                throw new QueryBuilderException('modelsCache `:key` key can not be start with `/`'/**m02053af65daa98380*/, ['key' => $_cacheOptions['key']]);
            }

            $_cacheOptions['key'] = $prefix . '/' . $_cacheOptions['key'];
        } else {
            $_cacheOptions['key'] = $prefix . '/' . md5($this->_sql . serialize($this->_bind));
        }

        return $_cacheOptions;
    }

    /**
     * @param array $rows
     * @param int   $total
     *
     * @return array
     */
    protected function _buildCacheData($rows, $total)
    {
        $from = $this->dispatcher->getModuleName() . ':' . $this->dispatcher->getControllerName() . ':' . $this->dispatcher->getActionName();

        $data = ['time' => date('Y-m-d H:i:s'), 'from' => $from, 'sql' => $this->_sql, 'bind' => $this->_bind, 'total' => $total, 'rows' => $rows];

        return $data;
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
     *
     * @return array
     * @throws \ManaPHP\Mvc\Model\QueryBuilder\Exception
     */
    public function execute()
    {
        self::$_hiddenParamNumber = 0;
        self::$_modelInstance = null;

        $this->_sql = $this->_buildSql();

        if ($this->_cacheOptions !== null) {
            $cacheOptions = $this->_getCacheOptions($this->_cacheOptions);

            $data = $this->modelsCache->get($cacheOptions['key']);
            if ($data !== false) {
                return json_decode($data, true)['rows'];
            }
        }

        $result = $this->modelsManager
            ->getReadConnection(end($this->_models))
            ->fetchAll($this->_sql, $this->_bind, \PDO::FETCH_ASSOC, $this->_index);

        if (isset($cacheOptions)) {
            $this->modelsCache->set($cacheOptions['key'],
                json_encode($this->_buildCacheData($result, -1), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                $cacheOptions['ttl']);
        }

        return $result;
    }

    /**
     * @return int
     * @throws \ManaPHP\Mvc\Model\QueryBuilder\Exception
     */
    protected function _getTotalRows()
    {
        if (count($this->_union) !== 0) {
            throw new QueryBuilderException('Union query is not support to get total rows'/**m0b24b0f0a54a1227c*/);
        }

        $this->_columns = 'COUNT(*) as [row_count]';
        $this->_limit = 0;
        $this->_offset = 0;
        $this->_order = null;

        $this->_sql = $this->_buildSql();

        if ($this->_group === null) {
            $result = $this->modelsManager
                ->getReadConnection(end($this->_models))
                ->fetchOne($this->_sql, $this->_bind);

            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $rowCount = (int)$result['row_count'];
        } else {
            $result = $this->modelsManager
                ->getReadConnection(end($this->_models))
                ->fetchAll($this->_sql, $this->_bind);
            $rowCount = count($result);
        }

        return $rowCount;
    }

    /**
     * @param int $size
     * @param int $page
     *
     * @return \ManaPHP\PaginatorInterface
     * @throws \ManaPHP\Paginator\Exception
     * @throws \ManaPHP\Mvc\Model\QueryBuilder\Exception
     */
    public function paginate($size, $page)
    {
        $this->paginator->items = $this->limit($size, ($page - 1) * $size)->executeEx($totalRows);
		
        return $this->paginator->paginate($totalRows, $size, $page);
    }

    /**
     * build the query and execute it.
     *
     * @param int|string $totalRows
     *
     * @return array
     * @throws \ManaPHP\Mvc\Model\QueryBuilder\Exception
     */
    public function executeEx(&$totalRows)
    {
        self::$_hiddenParamNumber = 0;
        self::$_modelInstance = null;

        $copy = clone $this;

        $this->_sql = $this->_buildSql();

        if ($this->_cacheOptions !== null) {
            $cacheOptions = $this->_getCacheOptions($this->_cacheOptions);

            $result = $this->modelsCache->get($cacheOptions['key']);

            if ($result !== false) {
                $result = json_decode($result, true);
                $totalRows = $result['total'];
                return $result['rows'];
            }
        }

        $result = $this->modelsManager
            ->getReadConnection(end($this->_models))
            ->fetchAll($this->_sql, $this->_bind, \PDO::FETCH_ASSOC, $this->_index);

        if (!$this->_limit) {
            $totalRows = count($result);
        } else {
            if (count($result) % $this->_limit === 0) {
                $totalRows = $copy->_getTotalRows();
            } else {
                $totalRows = $this->_offset + count($result);
            }
        }

        if (isset($cacheOptions)) {
            $this->modelsCache->set($cacheOptions['key'],
                json_encode($this->_buildCacheData($result, $totalRows), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                $cacheOptions['ttl']);
        }

        return $result;
    }

    /**
     * @param \ManaPHP\Mvc\Model\QueryBuilderInterface[] $builders
     *
     * @return static
     */
    public function unionAll($builders)
    {
        $this->_union = ['type' => 'UNION ALL', 'builders' => $builders];

        return $this;
    }

    /**
     * @param \ManaPHP\Mvc\Model\QueryBuilderInterface[] $builders
     *
     * @return static
     */
    public function unionDistinct($builders)
    {
        $this->_union = ['type' => 'UNION DISTINCT', 'builders' => $builders];

        return $this;
    }
}