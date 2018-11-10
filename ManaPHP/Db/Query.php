<?php
namespace ManaPHP\Db;

use ManaPHP\Di;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Model\Expression\Increment;
use ManaPHP\Model\Expression\Raw;
use ManaPHP\Model\ExpressionInterface;

/**
 * Class ManaPHP\Db\Model\QueryBuilder
 *
 * @package queryBuilder
 */
class Query extends \ManaPHP\Query implements QueryInterface
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
     * @var bool
     */
    protected $_forUpdate;

    /**
     * @var array
     */
    protected $_bind = [];

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
     * @param \ManaPHP\Model|string       $source
     * @param \ManaPHP\DbInterface|string $db
     */
    public function __construct($source = null, $db = null)
    {
        if (is_string($source) && strpos($source, '\\') !== false) {
            $source = new $source;
        }

        if ($source === null) {
            $this->_di = Di::getDefault();
        } elseif (is_string($source)) {
            $this->_di = Di::getDefault();
            $this->from($source);
        } else {
            $this->_di = $source->getDi();
            $this->_model = $source;
            $this->from(get_class($source));
            $this->_db = $source->getDb();
        }

        if ($db) {
            $this->_db = $db;
        }
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
     * @return \ManaPHP\DbInterface
     */
    public function getConnection()
    {
        return $this->_di->getShared($this->_db ?: 'db');
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
        /** @noinspection ForeachSourceInspection */
        foreach ($fields as $k => $v) {
            if (strpos($v, '[') === false && strpos($v, '(') === false) {
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
        if (!$this->_model && strpos($table, '\\') !== false) {
            $this->_model = $this->_di->getShared($table);
        }

        if ($alias) {
            $this->_tables = [$alias => $table];
        } else {
            $this->_tables = [$table];
        }

        if ($this->_db === null && $table instanceof self) {
            $this->_db = $table->_db;
        }

        return $this;
    }

    /**
     * @param string $table
     * @param string $alias
     *
     * @return static
     */
    public function addFrom($table, $alias = null)
    {
        if ($alias) {
            $this->_tables[$alias] = $table;
        } else {
            $this->_tables[] = $table;
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
        } elseif ($value === null && !is_string($filter)) {
            $this->_conditions[] = $filter;
        } elseif (is_array($value)) {
            if (!$value || isset($value[0])) {
                if (strpos($filter, '~=')) {
                    if (count($value) !== 2) {
                        throw new InvalidArgumentException(['`value of :filter` filter is invalid', 'filter' => $filter]);
                    }
                    $this->whereBetween(substr($filter, 0, -2), $value[0], $value[1]);
                } elseif (strpos($filter, '!=') || strpos($filter, '<>')) {
                    $this->whereNotIn(substr($filter, 0, -2), $value);
                } elseif (strpos($filter, '@=')) {
                    $this->whereDateBetween(substr($filter, 0, -2), $value[0], $value[1]);
                } elseif (strpos($filter, ' ') !== false) {
                    $this->_conditions[] = $filter;
                } else {
                    $this->whereIn(rtrim($filter, '='), $value);
                }
            } else {
                $this->_conditions[] = $filter;
                $this->_bind = array_merge($this->_bind, $value);
            }
        } elseif (preg_match('#^([\w\.]+)([<>=!^$*~,]*)$#', $filter, $matches) === 1) {
            list(, $field, $operator) = $matches;
            $bind_key = strtr($field, '.', '_');
            $normalizedField = preg_replace('#\w+#', '[\\0]', $field);
            if ($operator === '' || $operator === '=') {
                if ($value === null) {
                    $this->_conditions[] = $normalizedField . ' IS NULL';
                } else {
                    $this->_conditions[] = $normalizedField . '=:' . $bind_key;
                    $this->_bind[$bind_key] = $value;
                }
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
            } elseif ($operator === '^=') {
                $this->whereStartsWith($field, $value);
            } elseif ($operator === '$=') {
                $this->whereEndsWith($field, $value);
            } elseif ($operator === '*=') {
                $this->whereContains($field, $value);
            } elseif ($operator === ',=') {
                $this->whereInset($field, $value);
            } else {
                throw new NotSupportedException(['unknown `:where` where filter', 'where' => $filter]);
            }
        } elseif (preg_match('#^([\w\.]+)%(\d+)=$#', $filter, $matches) === 1) {
            $this->_conditions[] = $matches[0] . (int)$value;
        } else {
            throw new NotSupportedException(['unknown `:filter` filter', 'filter' => $filter]);
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
        if ($min === null || $min === '') {
            return $max === null || $max === '' ? $this : $this->where($expr . '<=', $max);
        } elseif ($max === null || $max === '') {
            return $min === null || $min === '' ? $this : $this->where($expr . '>=', $min);
        }

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
        if ($min === null || $min === '') {
            return $max === null || $max === '' ? $this : $this->where($expr . '>', $max);
        } elseif ($max === null || $max === '') {
            return $min === null || $min === '' ? $this : $this->where($expr . '<', $min);
        }

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
     */
    public function whereIn($expr, $values)
    {
        if ($values instanceof $this) {
            $this->where($expr . ' IN (' . $values->getSql() . ')', []);
            $this->_bind = array_merge($this->_bind, $values->getBind());
        } elseif ($values) {
            if (strpos($expr, '[') === false && strpos($expr, '(') === false) {
                if (strpos($expr, '.') !== false) {
                    $expr = '[' . str_replace('.', '].[', $expr) . ']';
                } else {
                    $expr = '[' . $expr . ']';
                }
            }

            if (is_int(current($values))) {
                $this->_conditions[] = $expr . ' IN (' . implode(', ', array_map('intval', $values)) . ')';
            } else {
                $bindKeys = [];
                /** @noinspection ForeachSourceInspection */
                foreach ($values as $k => $value) {
                    $key = '_in_' . $this->_hiddenParamNumber . '_' . $k;
                    $bindKeys[] = ":$key";
                    $this->_bind[$key] = $value;
                }

                $this->_conditions[] = $expr . ' IN (' . implode(', ', $bindKeys) . ')';
                $this->_hiddenParamNumber++;
            }
        } else {
            $this->_conditions[] = 'FALSE';
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
     */
    public function whereNotIn($expr, $values)
    {
        if ($values instanceof $this) {
            $this->where($expr . ' NOT IN (' . $values->getSql() . ')', []);
            $this->_bind = array_merge($this->_bind, $values->getBind());
        } elseif ($values) {
            if (strpos($expr, '[') === false && strpos($expr, '(') === false) {
                if (strpos($expr, '.') !== false) {
                    $expr = '[' . str_replace('.', '].[', $expr) . ']';
                } else {
                    $expr = '[' . $expr . ']';
                }
            }

            if (is_int(current($values))) {
                $this->_conditions[] = $expr . ' NOT IN (' . implode(', ', array_map('intval', $values)) . ')';
            } else {
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
     */
    public function notInWhere($expr, $values)
    {
        return $this->whereNotIn($expr, $values);
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
     * @param string|array $expr
     * @param string       $like
     *
     * @return static
     */
    public function whereLike($expr, $like)
    {
        if ($like === '') {
            return $this;
        }

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

            $this->where(implode(' OR ', $conditions), []);
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
     */
    public function whereNotLike($expr, $like)
    {
        if ($like === '') {
            return $this;
        }

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
     */
    public function whereContains($expr, $value)
    {
        return $value === '' ? $this : $this->whereLike($expr, '%' . $value . '%');
    }

    /**
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     */
    public function whereNotContains($expr, $value)
    {
        return $value === '' ? $this : $this->whereNotLike($expr, '%' . $value . '%');
    }

    /**
     * @param string|array $expr
     * @param string       $value
     * @param int          $length
     *
     * @return static
     */
    public function whereStartsWith($expr, $value, $length = null)
    {
        return $value === '' ? $this : $this->whereLike($expr, $length === null ? $value . '%' : str_pad($value, $length, '_'));
    }

    /**
     * @param string|array $expr
     * @param string       $value
     * @param int          $length
     *
     * @return static
     */
    public function whereNotStartsWith($expr, $value, $length = null)
    {
        return $value === '' ? $this : $this->whereNotLike($expr, $length === null ? $value . '%' : str_pad($value, $length, '_'));
    }

    /**
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     */
    public function whereEndsWith($expr, $value)
    {
        return $value === '' ? $this : $this->whereLike($expr, '%' . $value);
    }

    /**
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     */
    public function whereNotEndsWith($expr, $value)
    {
        return $value === '' ? $this : $this->whereNotLike($expr, '%' . $value);
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

        if ($bind) {
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
     */
    protected function _buildSql()
    {
        if ($this->_db === null || is_string($this->_db)) {
            $this->_db = $this->_di->getShared($this->_db ?: 'db');
        }

        if ($this->_union) {
            return $this->_getUnionSql();
        }

        if (!$this->_tables) {
            throw new MisuseException('at least one model is required to build the query');
        }

        $this->_replaceModelInfo();

        $params = [];
        if ($this->_distinct) {
            $params['distinct'] = true;
        }

        if ($this->_fields !== null) {
            $fields = $this->_fields;
        } elseif (count($this->_tables) === 1) {
            $fields = '*';
        } else {
            $fields = '';
            $selectedFields = [];
            foreach ($this->_tables as $alias => $table) {
                $selectedFields[] = '[' . (is_int($alias) ? $table : $alias) . '].*';
            }
            $fields .= implode(', ', $selectedFields);
        }
        $params['fields'] = $fields;

        $selectedTables = [];

        foreach ($this->_tables as $alias => $table) {
            if ($table instanceof $this) {
                if (is_int($alias)) {
                    throw new NotSupportedException('if using SubQuery, you must assign an alias for it');
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
                    throw new NotSupportedException('if using SubQuery, you must assign an alias for it');
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
            $wheres[] = stripos($v, ' or ') ? "($v)" : $v;
        }

        if ($wheres) {
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
     * @return $this
     */
    protected function _replaceModelInfo()
    {
        foreach ($this->_tables as $alias => $table) {
            /** @var \ManaPHP\Model $model */
            if (is_int($alias)) {
                if (is_string($table) && strpos($table, '\\') !== false) {
                    $model = $this->_di->getShared($table);
                    $this->_tables[$alias] = $model->getSource($this->_bind);
                }
            } else {
                if (strpos($table, '\\') !== false) {
                    $model = $this->_di->getShared($table);
                    $this->_tables[$alias] = $model->getSource($this->_bind);
                }
            }
        }

        foreach ($this->_joins as $k => $join) {
            $table = $join[0];
            if (is_string($table) && strpos($table, '\\') !== false) {
                /** @var \ManaPHP\Model $model */
                $model = $this->_di->getShared($table);
                $this->_joins[$k][0] = $model->getSource($this->_bind);
            }
        }

        return $this;
    }

    /**
     *
     * @return array
     */
    public function execute()
    {
        $this->_hiddenParamNumber = 0;

        $this->_sql = $this->_buildSql();

        if (in_array('FALSE', $this->_conditions, true)) {
            $this->logger->debug($this->_sql, 'db.query.skip');
            return [];
        }

        $db = $this->_forceUseMaster ? $this->_db->getMasterConnection() : $this->_db;
        return $db->fetchAll($this->_sql, $this->_bind, \PDO::FETCH_ASSOC, $this->_index);
    }

    /**
     * @param array $expr
     *
     * @return array
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
     * @param string $field
     *
     * @return int
     */
    public function count($field = '*')
    {
        if ($this->_union) {
            throw new NotSupportedException('Union query is not support to get total rows');
        }

        $copy = clone $this;

        $copy->_fields = "COUNT($field) as [row_count]";
        $copy->_limit = null;
        $copy->_offset = null;
        $copy->_order = null;
        $copy->_index = null;

        $copy->_sql = $copy->_buildSql();

        if ($copy->_group === null) {
            $result = $copy->_db->fetchOne($copy->_sql, $copy->_bind);

            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $rowCount = (int)$result['row_count'];
        } else {
            $result = $copy->_db->fetchAll($copy->_sql, $copy->_bind);
            $rowCount = count($result);
        }

        return $rowCount;
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
     * @deprecated
     * @return array|false
     */
    public function fetchOne()
    {
        $r = $this->limit(1)->execute();

        return $r ? $r[0] : false;
    }

    /**
     * @deprecated
     * @return array
     */
    public function fetchAll()
    {
        return $this->execute();
    }

    /**
     * @param string $field
     *
     * @return array
     */
    public function values($field)
    {
        $values = [];
        foreach ($this->distinct()->select([$field])->all() as $v) {
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
        foreach ($fieldValues as $field => $value) {
            if ($value instanceof ExpressionInterface) {
                if ($value instanceof Increment) {
                    $fieldValues[] = "[$field]=[$field]" . ($value->step >= 0 ? '+' : '') . $value->step;
                } elseif ($value instanceof Raw) {
                    $fieldValues[] = "[$field]=" . $value->expression;
                }
                unset($fieldValues[$field]);
            }
        }

        return $this->_replaceModelInfo()->getConnection()->update($this->_tables[0], $fieldValues, $this->_conditions, $this->_bind);
    }

    /**
     * @return int
     */
    public function delete()
    {
        return $this->_replaceModelInfo()->getConnection()->delete($this->_tables[0], $this->_conditions, $this->_bind);
    }
}
