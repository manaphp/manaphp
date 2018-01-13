<?php
namespace ManaPHP\Db;

use ManaPHP\Component;
use ManaPHP\Db\Query\Exception as QueryException;
use ManaPHP\Di;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Db\Model\QueryBuilder
 *
 * @package queryBuilder
 *
 * @property \ManaPHP\Cache\EngineInterface   $modelsCache
 * @property \ManaPHP\Paginator               $paginator
 * @property \ManaPHP\Mvc\DispatcherInterface $dispatcher
 * @property \ManaPHP\Http\RequestInterface   $request
 */
class Query extends Component implements QueryInterface
{
    /**
     * @var array
     */
    protected $_fields;

    /**
     * @var array
     */
    protected $_tables = [];

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
    protected $_limit;

    /**
     * @var int
     */
    protected $_offset;

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
    protected $_hiddenParamNumber = 0;

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
     * @var bool
     */
    protected $_forceUseMaster = false;

    /**
     * @var \ManaPHP\DbInterface
     */
    protected $_db;

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
     * @param \ManaPHP\DbInterface|string $db
     */
    public function __construct($db = null)
    {
        $this->_db = $db;

        $this->_dependencyInjector = Di::getDefault();
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
     * @param string|array $fields
     *
     * @return static
     */
    public function select($fields)
    {
        if (is_string($fields)) {
            $fields = str_replace(["\t", "\r", "\n"], '', $fields);
            if (strpos($fields, '[') === false && strpos($fields, '(') === false) {
                $fields = (string)preg_replace('#\w+#', '[\\0]', $fields);
                $fields = (string)str_ireplace('[as]', 'AS', $fields);
                $fields = (string)preg_replace('#\s+#', ' ', $fields);
            }

            $this->_fields = $fields;
        } else {
            $r = '';
            foreach ($fields as $k => $v) {
                if (strpos($v, '[') === false && strpos($v, '(') === false) {
                    if (is_int($k)) {
                        $r .= preg_replace('#\w+#', '[\\0]', $v) . ', ';
                    } else {
                        $r .= preg_replace('#\w+#', '[\\0]', $v) . ' AS [' . $k . '], ';
                    }
                } else {
                    if (is_int($k)) {
                        $r .= $v . ', ';
                    } else {
                        $r .= $v . ' AS [' . $k . '], ';
                    }
                }
            }
            $this->_fields = substr($r, 0, -2);
        }

        return $this;
    }

    /**
     *
     *<code>
     *    $builder->from('Robots');
     *</code>
     *
     * @param string $table
     * @param string $alias
     *
     * @return static
     */
    public function from($table, $alias = null)
    {
        if (is_string($alias)) {
            $this->_tables[$alias] = $table;
        } else {
            $this->_tables[] = $table;
        }

        if ($this->_db === null && $table instanceof self) {
            $this->_db = $table->_db;
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
     * @param string|\ManaPHP\Db\QueryInterface $table
     * @param string                            $condition
     * @param string                            $alias
     * @param string                            $type
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
     * Adds a INNER join to the query
     *
     *<code>
     *    $builder->innerJoin('Robots');
     *    $builder->innerJoin('Robots', 'r.id = RobotsParts.robots_id');
     *    $builder->innerJoin('Robots', 'r.id = RobotsParts.robots_id', 'r');
     *</code>
     *
     * @param string|\ManaPHP\Db\QueryInterface $table
     * @param string                            $condition
     * @param string                            $alias
     *
     * @return static
     */
    public function innerJoin($table, $condition = null, $alias = null)
    {
        return $this->join($table, $condition, $alias, 'INNER');
    }

    /**
     * Adds a LEFT join to the query
     *
     *<code>
     *    $builder->leftJoin('Robots', 'r.id = RobotsParts.robots_id', 'r');
     *</code>
     *
     * @param string|\ManaPHP\Db\QueryInterface $table
     * @param string                            $condition
     * @param string                            $alias
     *
     * @return static
     */
    public function leftJoin($table, $condition = null, $alias = null)
    {
        return $this->join($table, $condition, $alias, 'LEFT');
    }

    /**
     * Adds a RIGHT join to the query
     *
     *<code>
     *    $builder->rightJoin('Robots', 'r.id = RobotsParts.robots_id', 'r');
     *</code>
     *
     * @param string|\ManaPHP\Db\QueryInterface $table
     * @param string                            $condition
     * @param string                            $alias
     *
     * @return static
     */
    public function rightJoin($table, $condition = null, $alias = null)
    {
        return $this->join($table, $condition, $alias, 'RIGHT');
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
     * @throws \ManaPHP\Db\Query\Exception
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
        } elseif ($value === null) {
            $this->_conditions[] = $filter;
        } elseif (is_array($value)) {
            if (strpos($filter, '~=')) {
                if (count($value) !== 2 || !isset($value[0], $value[1])) {
                    throw new QueryException('`:filter` filter is valid: value is not a two elements array', ['filter' => $filter]);
                }

                if (is_string($value[0]) && is_string($value[1]) && strpos($value[0], '-') !== false && strpos($value[1], '-') !== false) {
                    /** @noinspection NestedPositiveIfStatementsInspection */
                    if (preg_match('#^\d{4}-\d{2}-\d{2}$#', $value[0]) && preg_match('#^\d{4}-\d{2}-\d{2}$#', $value[1])) {
                        $value[0] = strtotime($value[0]);
                        $value[1] = strtotime($value[1] . 'next day') - 1;
                    }
                }
                $this->whereBetween(substr($filter, 0, -2), $value[0], $value[1]);
            } elseif (isset($value[0]) || count($value) === 0) {
                if (strpos($filter, '!=') || strpos($filter, '<>')) {
                    $this->whereNotIn(substr($filter, 0, -2), $value);
                } else {
                    $this->whereIn(rtrim($filter, '='), $value);
                }
            } else {
                $this->_conditions[] = $filter;
                $this->_bind = array_merge($this->_bind, $value);
            }
        } elseif (preg_match('#^([\w\.]+)\s*([<>=!^$*~]*)$#', $filter, $matches) === 1) {
            list(, $field, $operator) = $matches;
            if ($operator === '') {
                $operator = '=';
            }

            $bind_key = strtr($field, '.', '_');
            $normalizedField = preg_replace('#\w+#', '[\\0]', $field);
            if (in_array($operator, ['=', '>', '>=', '<', '<=', '!=', '<>'], true)) {
                $this->_conditions[] = $normalizedField . $operator . ':' . $bind_key;
                $this->_bind[$bind_key] = $value;
            } elseif ($operator === '^=') {
                $this->whereStartsWith($field, $value);
            } elseif ($operator === '$=') {
                $this->whereEndsWith($field, $value);
            } elseif ($operator === '*=') {
                $this->whereContains($field, $value);
            } elseif ($operator === '~=') {
                $this->whereLike($field, $value);
            } else {
                throw new QueryException('unknown `:where` where filter', ['where' => $filter]);
            }
        } else {
            throw new QueryException('unknown `:filter` filter', ['filter' => $filter]);
        }

        return $this;
    }

    /**
     * @param array $fields
     *
     * @return static
     * @throws \ManaPHP\Db\Query\Exception
     */
    public function whereRequest($fields)
    {
        foreach ($fields as $k => $v) {
            if (strpos($v, '.') === false) {
                $field = $v;
            } else {
                $parts = explode('.', $v);
                $field = $parts[1];
            }
            $value = $this->request->get(rtrim($field, '=!<>~*^$'));
            if ($value === null) {
                continue;
            } elseif (is_string($value)) {
                $value = trim($value);
                if ($value === '') {
                    continue;
                }
            } elseif (is_array($value)) {
                if (count($value) === 1 && trim($value[0]) === '') {
                    continue;
                }
            }

            $this->where($v, $value);
        }

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
            $minKey = '_min_' . $this->_hiddenParamNumber;
            $maxKey = '_max_' . $this->_hiddenParamNumber;
            $this->_hiddenParamNumber++;
        }

        $this->_conditions[] = "$expr BETWEEN :$minKey AND :$maxKey";

        $this->_bind[$minKey] = $min;
        $this->_bind[$maxKey] = $max;

        return $this;
    }

    /**
     * alias of whereBetween
     *
     * @param string           $expr
     * @param int|float|string $min
     * @param int|float|string $max
     *
     * @return static
     * @deprecated
     */
    public function betweenWhere($expr, $min, $max)
    {
        return $this->whereBetween($expr, $min, $max);
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
        $minKey = '_min_' . $this->_hiddenParamNumber;
        $maxKey = '_max_' . $this->_hiddenParamNumber;

        $this->_hiddenParamNumber++;

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
     * alias of whereNotBetween
     *
     * @param string           $expr
     * @param int|float|string $min
     * @param int|float|string $max
     *
     * @return static
     * @deprecated
     */
    public function notBetweenWhere($expr, $min, $max)
    {
        return $this->whereNotBetween($expr, $min, $max);
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
     * @throws \ManaPHP\Db\Query\Exception
     */
    public function whereIn($expr, $values)
    {
        if ($values instanceof $this) {
            $this->where($expr . ' IN (' . $values->getSql() . ')');
            $this->_bind = array_merge($this->_bind, $values->getBind());
        } else {
            if (count($values) === 0) {
                $this->_conditions[] = '1=2';
            } else {
                if (strpos($expr, '[') === false && strpos($expr, '(') === false) {
                    if (strpos($expr, '.') !== false) {
                        $expr = '[' . str_replace('.', '].[', $expr) . ']';
                    } else {
                        $expr = '[' . $expr . ']';
                    }
                }

                $bindKeys = [];

                /** @noinspection ForeachSourceInspection */
                foreach ($values as $k => $value) {
                    $key = '_in_' . $this->_hiddenParamNumber . '_' . $k;
                    $bindKeys[] = ":$key";
                    $this->_bind[$key] = $value;
                }

                $this->_conditions[] = $expr . ' IN (' . implode(', ', $bindKeys) . ')';
            }

            $this->_hiddenParamNumber++;
        }

        return $this;
    }

    /**
     * alias of whereIn
     *
     * @param string                           $expr
     * @param array|\ManaPHP\Db\QueryInterface $values
     *
     * @return static
     * @deprecated
     * @throws \ManaPHP\Db\Query\Exception
     */
    public function inWhere($expr, $values)
    {
        return $this->whereIn($expr, $values);
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
     * @throws \ManaPHP\Db\Query\Exception
     */
    public function whereNotIn($expr, $values)
    {
        if ($values instanceof $this) {
            $this->where($expr . ' NOT IN (' . $values->getSql() . ')');
            $this->_bind = array_merge($this->_bind, $values->getBind());
        } else {
            if (count($values) !== 0) {
                if (strpos($expr, '[') === false && strpos($expr, '(') === false) {
                    if (strpos($expr, '.') !== false) {
                        $expr = '[' . str_replace('.', '].[', $expr) . ']';
                    } else {
                        $expr = '[' . $expr . ']';
                    }
                }

                $bindKeys = [];

                /** @noinspection ForeachSourceInspection */
                foreach ($values as $k => $value) {
                    $key = '_in_' . $this->_hiddenParamNumber . '_' . $k;
                    $bindKeys[] = ':' . $key;
                    $this->_bind[$key] = $value;
                }

                $this->_hiddenParamNumber++;

                $this->_conditions[] = $expr . ' NOT IN (' . implode(', ', $bindKeys) . ')';
            }
        }
        return $this;
    }

    /**
     * alias of whereNotIn
     *
     * @param string                           $expr
     * @param array|\ManaPHP\Db\QueryInterface $values
     *
     * @return static
     * @deprecated
     * @throws \ManaPHP\Db\Query\Exception
     */
    public function notInWhere($expr, $values)
    {
        return $this->whereNotIn($expr, $values);
    }

    /**
     * @param string|array $expr
     * @param string       $like
     *
     * @return static
     * @throws \ManaPHP\Db\Query\Exception
     */
    public function whereLike($expr, $like)
    {
        if (is_array($expr)) {
            $conditions = [];
            /** @noinspection ForeachSourceInspection */
            foreach ($expr as $field) {
                $key = strtr($field, '.', '_');
                if (strpos($field, '.') !== false) {
                    $conditions[] = '[' . str_replace('.', '].[', $field) . ']' . ' LIKE :' . $key;
                } else {
                    $conditions[] = '[' . $field . '] LIKE :' . $key;
                }

                $this->_bind[$key] = $like;
            }

            $this->where(implode(' OR ', $conditions));
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
     * @throws \ManaPHP\Db\Query\Exception
     */
    public function whereNotLike($expr, $like)
    {
        if (is_array($expr)) {
            $conditions = [];
            /** @noinspection ForeachSourceInspection */
            foreach ($expr as $field) {
                $key = strtr($field, '.', '_');
                if (strpos($field, '.') !== false) {
                    $conditions[] = '[' . str_replace('.', '].[', $field) . ']' . ' NOT LIKE :' . $key;
                } else {
                    $conditions[] = '[' . $field . '] NOT LIKE :' . $key;
                }

                $this->_bind[$key] = $like;
            }

            $this->where(implode(' AND ', $conditions));
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
     * @throws \ManaPHP\Db\Query\Exception
     */
    public function whereContains($expr, $value)
    {
        return $this->whereLike($expr, '%' . $value . '%');
    }

    /**
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     * @throws \ManaPHP\Db\Query\Exception
     */
    public function whereNotContains($expr, $value)
    {
        return $this->whereNotLike($expr, '%' . $value . '%');
    }

    /**
     * @param string|array $expr
     * @param string       $value
     * @param int          $length
     *
     * @return static
     * @throws \ManaPHP\Db\Query\Exception
     */
    public function whereStartsWith($expr, $value, $length = null)
    {
        return $this->whereLike($expr, $length === null ? $value . '%' : str_pad($value, $length, '_'));
    }

    /**
     * @param string|array $expr
     * @param string       $value
     * @param int          $length
     *
     * @return static
     * @throws \ManaPHP\Db\Query\Exception
     */
    public function whereNotStartsWith($expr, $value, $length = null)
    {
        return $this->whereNotLike($expr, $length === null ? $value . '%' : str_pad($value, $length, '_'));
    }

    /**
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     * @throws \ManaPHP\Db\Query\Exception
     */
    public function whereEndsWith($expr, $value)
    {
        return $this->whereLike($expr, '%' . $value);
    }

    /**
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     * @throws \ManaPHP\Db\Query\Exception
     */
    public function whereNotEndsWith($expr, $value)
    {
        return $this->whereNotLike($expr, '%' . $value);
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
     */
    public function orderBy($orderBy)
    {
        if (is_string($orderBy)) {
            if (strpos($orderBy, '[') === false && strpos($orderBy, '(') === false) {
                $orderBy = (string)preg_replace('#\w+#', '[\\0]', $orderBy);
                $orderBy = str_ireplace(['[ASC]', '[DESC]'], ['ASC', 'DESC'], $orderBy);
            }
            $this->_order = $orderBy;
        } else {
            $r = '';
            /** @noinspection ForeachSourceInspection */
            foreach ($orderBy as $k => $v) {
                if (is_int($k)) {
                    $type = 'ASC';
                    $field = $v;
                } else {
                    $field = $k;
                    if (is_int($v)) {
                        $type = $v === SORT_ASC ? 'ASC' : 'DESC';
                    } else {
                        $type = strtoupper($v);
                    }
                }

                if (strpos($field, '[') === false && strpos($field, '(') === false) {
                    if (strpos($field, '.') !== false) {
                        $r .= '[' . str_replace('.', '].[', $field) . '] ' . $type . ', ';
                    } else {
                        $r .= '[' . $field . '] ' . $type . ', ';
                    }
                }
                $this->_order = substr($r, 0, -2);
            }
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
                /** @noinspection ForeachSourceInspection */
                foreach ($having as $item) {
                    $items[] = '(' . $item . ')';
                }
                $this->_having = implode(' AND ', $items);
            }
        } else {
            $this->_having = $having;
        }

        if (count($bind) !== 0) {
            $this->_bind = array_merge($this->_bind, $bind);
        }

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
    public function page($size = null, $page = null)
    {
        if ($size === null) {
            $size = $this->request->get('size', 'int', 10);
        }

        if ($page === null) {
            $page = $this->request->get('page', 'int', 1);
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
     */
    public function groupBy($groupBy)
    {
        if (is_string($groupBy)) {
            if (strpos($groupBy, '[') === false && strpos($groupBy, '(') === false) {
                $this->_group = preg_replace('#\w+#', '[\\0]', $groupBy);
            } else {
                $this->_group = $groupBy;
            }
        } else {
            $r = '';
            /** @noinspection ForeachSourceInspection */
            foreach ($groupBy as $item) {
                if (strpos($item, '[') === false && strpos($item, '(') === false) {
                    $r .= preg_replace('#\w+#', '[\\0]', $item) . ', ';
                } else {
                    $r .= $item . ', ';
                }
            }
            $this->_group = substr($r, 0, -2);
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
     * @return string
     */
    protected function _getUnionSql()
    {
        $unions = [];

        /**
         * @var \ManaPHP\Db\QueryInterface $queries
         */
        /** @noinspection ForeachSourceInspection */
        foreach ($this->_union['queries'] as $queries) {
            $unions[] = '(' . $queries->getSql() . ')';

            /** @noinspection SlowArrayOperationsInLoopInspection */
            $this->_bind = array_merge($this->_bind, $queries->getBind());
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
        if ($this->_limit !== null) {
            $params['limit'] = $this->_limit;
        }

        if ($this->_offset !== null) {
            $params['offset'] = $this->_offset;
        }

        $sql .= $this->_db->buildSql($params);

        $this->_tables[] = $queries->getTables()[0];

        return $sql;
    }

    /**
     * @return string
     * @throws \ManaPHP\Db\Query\Exception
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
     * @throws \ManaPHP\Db\Query\Exception
     */
    protected function _buildSql()
    {
        if ($this->_db === null || is_string($this->_db)) {
            $this->_db = $this->_dependencyInjector->getShared($this->_db ?: 'db');
        }

        if (count($this->_union) !== 0) {
            return $this->_getUnionSql();
        }

        if (count($this->_tables) === 0) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new QueryException('at least one model is required to build the query'/**m09d10c2135a4585fa*/);
        }

        $params = [];
        if ($this->_distinct) {
            $params['distinct'] = true;
        }

        if ($this->_fields !== null) {
            $fields = $this->_fields;
        } else {
            if (count($this->_tables) === 1) {
                $fields = '*';
            } else {
                $fields = '';
                $selectedFields = [];
                foreach ($this->_tables as $alias => $table) {
                    $selectedFields[] = '[' . (is_int($alias) ? $table : $alias) . '].*';
                }
                $fields .= implode(', ', $selectedFields);
            }
        }
        $params['fields'] = $fields;

        $selectedTables = [];

        foreach ($this->_tables as $alias => $table) {
            if ($table instanceof $this) {
                if (is_int($alias)) {
                    /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                    throw new QueryException('if using SubQuery, you must assign an alias for it'/**m0e5f4aa93dc102dde*/);
                }

                $selectedTables[] = '(' . $table->getSql() . ') AS [' . $alias . ']';
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $this->_bind = array_merge($this->_bind, $table->getBind());
            } else {
                if (is_string($alias)) {
                    $selectedTables[] = '[' . $table . '] AS [' . $alias . ']';
                } else {
                    $selectedTables[] = '[' . $table . ']';
                }
            }
        }

        $params['from'] = implode(', ', $selectedTables);

        $joinSQL = '';
        /** @noinspection ForeachSourceInspection */
        foreach ($this->_joins as $join) {
            list($joinTable, $joinCondition, $joinAlias, $joinType) = $join;

            if ($joinAlias !== null) {
                $this->_tables[$joinAlias] = $joinTable;
            } else {
                $this->_tables[] = $joinTable;
            }

            if ($joinType !== null) {
                $joinSQL .= ' ' . $joinType;
            }

            if ($joinTable instanceof $this) {
                $joinSQL .= ' JOIN (' . $joinTable->getSql() . ')';
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $this->_bind = array_merge($this->_bind, $joinTable->getBind());
                if ($joinAlias === null) {
                    throw new QueryException('if using SubQuery, you must assign an alias for it'/**m0a80f96a41e1596cb*/);
                }
            } else {
                $joinSQL .= ' JOIN [' . $joinTable . ']';
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
        foreach ($this->_conditions as $v) {
            $wheres[] = Text::contains($v, ' or ', true) ? "($v)" : $v;
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

        if ($this->_limit !== null) {
            $params['limit'] = $this->_limit;
        }

        if ($this->_offset !== null) {
            $params['offset'] = $this->_offset;
        }

        if ($this->_forUpdate) {
            $params['forUpdate'] = $this->_forUpdate;
        }

        $sql = $this->_db->buildSql($params);
        //compatible with other SQL syntax
        $replaces = [];
        foreach ($this->_bind as $key => $_) {
            $replaces[':' . $key . ':'] = ':' . $key;
        }

        $sql = strtr($sql, $replaces);

        foreach ($this->_tables as $table) {
            if (!$table instanceof $this) {
                $source = $table;
                if (strpos($source, '.')) {
                    $source = '[' . implode('].[', explode('.', $source)) . ']';
                } else {
                    $source = '[' . $source . ']';
                }
                $sql = str_replace('[' . $table . ']', $source, $sql);
            }
        }

        return $sql;
    }

    /**
     * @param string $key
     *
     * @return array
     */
    public function getBind($key = null)
    {
        if ($key !== null) {
            return isset($this->_bind[$key]) ? $this->_bind[$key] : null;
        } else {
            return $this->_bind;
        }
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
    public function getTables()
    {
        return $this->_tables;
    }

    /**
     * @return array
     * @throws \ManaPHP\Db\Query\Exception
     */
    protected function _getCacheOptions()
    {
        $cacheOptions = is_array($this->_cacheOptions) ? $this->_cacheOptions : ['ttl' => $this->_cacheOptions];
        if (!isset($cacheOptions['key'])) {
            if ($cacheOptions['key'][0] === '/') {
                throw new QueryException('modelsCache `:key` key can not be start with `/`'/**m02053af65daa98380*/, ['key' => $cacheOptions['key']]);
            }

            $cacheOptions['key'] = md5($this->_sql . serialize($this->_bind));
        }

        return $cacheOptions;
    }

    /**
     * @param array $items
     * @param int   $count
     *
     * @return array
     */
    protected function _buildCacheData($items, $count)
    {
        return ['time' => date('Y-m-d H:i:s'), 'sql' => $this->_sql, 'bind' => $this->_bind, 'count' => $count, 'items' => $items];
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
     * @throws \ManaPHP\Db\Query\Exception
     */
    public function execute()
    {
        $this->_hiddenParamNumber = 0;

        $this->_sql = $this->_buildSql();

        if ($this->_cacheOptions !== null) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $cacheOptions = $this->_getCacheOptions();

            $data = $this->modelsCache->get($cacheOptions['key']);
            if ($data !== false) {
                $this->fireEvent('modelsCache:hit', ['key' => $cacheOptions['key'], 'sql' => $this->_sql]);

                return json_decode($data, true)['items'];
            }
            $this->fireEvent('modelsCache:miss', ['key' => $cacheOptions['key'], 'sql' => $this->_sql]);
        }

        $result = ($this->_forceUseMaster ? $this->_db->getMasterConnection() : $this->_db)->fetchAll($this->_sql, $this->_bind, \PDO::FETCH_ASSOC, $this->_index);
        if (isset($cacheOptions)) {
            $this->modelsCache->set($cacheOptions['key'],
                json_encode($this->_buildCacheData($result, -1), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                $cacheOptions['ttl']);
        }

        return $result;
    }

    /**
     * @param array $expr
     *
     * @return array
     * @throws \ManaPHP\Db\Query\Exception
     */
    public function aggregate($expr)
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

        $this->_fields = substr($fields, 0, -2);

        return $this->execute();
    }

    /**
     * @return int
     * @throws \ManaPHP\Db\Query\Exception
     */
    protected function _getTotalRows()
    {
        if (count($this->_union) !== 0) {
            throw new QueryException('Union query is not support to get total rows'/**m0b24b0f0a54a1227c*/);
        }

        $this->_fields = 'COUNT(*) as [row_count]';
        $this->_limit = null;
        $this->_offset = null;
        $this->_order = null;

        $this->_sql = $this->_buildSql();

        if ($this->_group === null) {
            $result = $this->_db->fetchOne($this->_sql, $this->_bind);

            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $rowCount = (int)$result['row_count'];
        } else {
            $result = $this->_db->fetchAll($this->_sql, $this->_bind);
            $rowCount = count($result);
        }

        return $rowCount;
    }

    /**
     * @param int $size
     * @param int $page
     *
     * @return \ManaPHP\Paginator
     * @throws \ManaPHP\Db\Query\Exception
     */
    public function paginate($size = null, $page = null)
    {
        $this->page($size, $page);

        $this->_hiddenParamNumber = 0;

        $copy = clone $this;

        $this->_sql = $this->_buildSql();

        do {
            if ($this->_cacheOptions !== null) {
                $cacheOptions = $this->_getCacheOptions();

                if (($result = $this->modelsCache->get($cacheOptions['key'])) !== false) {
                    $result = json_decode($result, true);

                    $count = $result['count'];
                    $items = $result['items'];

                    $this->fireEvent('modelsCache:hit', ['key' => $cacheOptions['key'], 'sql' => $this->_sql]);
                    break;
                }
                $this->fireEvent('modelsCache:miss', ['key' => $cacheOptions['key'], 'sql' => $this->_sql]);
            }

            /** @noinspection SuspiciousAssignmentsInspection */
            $items = $this->_db->fetchAll($this->_sql, $this->_bind, \PDO::FETCH_ASSOC, $this->_index);

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
                    json_encode($this->_buildCacheData($items, $count), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    $cacheOptions['ttl']);
            }
        } while (false);

        $this->paginator->items = $items;

        return $this->paginator->paginate($count, $this->_limit, (int)($this->_offset / $this->_limit) + 1);
    }

    /**
     * @return bool
     * @throws \ManaPHP\Db\Query\Exception
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
     * @param string $field
     *
     * @return int
     * @throws \ManaPHP\Db\Query\Exception
     */
    public function count($field = '*')
    {
        return $this->aggregate(['count' => 'COUNT(*)'])[0]['count'];
    }

    /**
     * @param \ManaPHP\Db\QueryInterface[] $queries
     * @param bool                         $distinct
     *
     * @return static
     */
    public function union($queries, $distinct = false)
    {
        if ($this->_db === null) {
            foreach ($queries as $query) {
                if ($query instanceof self) {
                    $this->_db = $query->_db;
                    break;
                }
            }
        }
        $this->_union = ['type' => 'UNION ' . ($distinct ? 'DISTINCT' : 'ALL'), 'queries' => $queries];

        return $this;
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
     * @return array|false
     * @throws \ManaPHP\Db\Query\Exception
     */
    public function fetchOne()
    {
        $r = $this->limit(1)->fetchAll();

        return count($r) === 0 ? false : $r[0];
    }

    /**
     *
     * @return array
     * @throws \ManaPHP\Db\Query\Exception
     */
    public function fetchAll()
    {
        return $this->execute();
    }

    /**
     * @param string $field
     *
     * @return array
     * @throws \ManaPHP\Db\Query\Exception
     */
    public function distinctValues($field)
    {
        $values = [];
        foreach ($this->distinct()->select($field)->fetchAll() as $v) {
            $values[] = $v[$field];
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
        return $this->_db->update($this->_tables[0], $fieldValues, $this->_conditions, $this->_bind);
    }

    /**
     * @return int
     */
    public function delete()
    {
        return $this->_db->delete($this->_tables[0], $this->_conditions, $this->_bind);
    }
}
