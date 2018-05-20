<?php
namespace ManaPHP\Mongodb\Model;

use ManaPHP\Component;
use ManaPHP\Di;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Mongodb\Model\Criteria\Exception as CriteriaException;
use MongoDB\BSON\Regex;

/**
 * Class ManaPHP\Mongodb\Model\Criteria
 *
 * @package ManaPHP\Mongodb\Model
 *
 * @property \ManaPHP\Paginator             $paginator
 * @property \ManaPHP\CacheInterface        $modelsCache
 * @property \ManaPHP\Http\RequestInterface $request
 * @property \ManaPHP\Mongodb\Model         $_model
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
     * @var array
     */
    protected $_filters = [];

    /**
     * @var string
     */
    protected $_order;

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
     * @param string|\ManaPHP\Mongodb\Model $model
     * @param string|array                  $fields
     */
    public function __construct($model, $fields = null)
    {
        $this->_model = is_string($model) ? new $model : $model;
        $this->_di = Di::getDefault();

        if ($fields !== null) {
            $this->select($fields);
        }
    }

    /**
     * Sets SELECT DISTINCT / SELECT ALL flag
     *
     * @param string $field
     *
     * @return array
     * @throws \ManaPHP\Mongodb\Model\Criteria\Exception
     */
    public function values($field)
    {
        $source = $this->_model->getSource();

        /**
         * @var \ManaPHP\MongodbInterface $db
         */
        $db = $this->_di->getShared($this->_model->getDb());

        $cmd = ['distinct' => $source, 'key' => $field];
        if ($this->_filters) {
            $cmd['query'] = ['$and' => $this->_filters];
        }

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $cursor = $db->command($cmd);
        $r = $cursor->toArray()[0];
        if (!$r['ok']) {
            throw new CriteriaException([
                '`:distinct` distinct for `:collection` collection failed `:code`: `:msg`',
                'distinct' => $field,
                'code' => $r['code'],
                'msg' => $r['errmsg'],
                'collection' => $source
            ]);
        }

        return $this->_limit ? array_slice($r['values'], $this->_offset, $this->_limit) : $r['values'];
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
                throw new CriteriaException(['`:aggregate` aggregate is invalid.', 'aggregate' => $v]);
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
                    throw new CriteriaException(['unknown `:operand` operand of `:aggregate` aggregate', 'operand' => $operand, 'aggregate' => $v]);
                }
            } else {
                throw new CriteriaException([
                    'unknown `:accumulator` accumulator of `:aggregate` aggregate',
                    'accumulator' => $accumulator,
                    'aggregate' => $v
                ]);
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
            $this->_filters[] = is_string($filter) ? [$filter => null] : $filter;
        } elseif (is_array($value)) {
            if (strpos($filter, '~=')) {
                if (count($value) === 2 && gettype($value[0]) === gettype($value[1])) {
                    $this->whereBetween(substr($filter, 0, -2), $value[0], $value[1]);
                } else {
                    $this->_filters[] = [substr($filter, 0, -2) => ['$in' => $value]];
                }
            } else if (isset($value[0]) || !$value) {
                if (strpos($filter, '!=') || strpos($filter, '<>')) {
                    $this->whereNotIn(substr($filter, 0, -2), $value);
                } else {
                    if (in_array(null, $value, true)) {
                        $this->_filters[] = [$filter => ['$in' => $value]];
                    } else {
                        $this->whereIn(rtrim($filter, '='), $value);
                    }
                }
            } else {
                $this->_filters[] = [$filter => $value];
            }
        } elseif (preg_match('#^([\w\.]+)\s*([<>=!^$*~@,]*)$#', $filter, $matches) === 1) {
            list(, $field, $operator) = $matches;

            if ($operator === '' || $operator === '=') {
                $fieldTypes = $this->_model->getFieldTypes();
                $this->_filters[] = [$field => $this->_model->getNormalizedValue($fieldTypes[$field], $value)];
            } elseif ($operator === '~=') {
                $field = substr($filter, 0, -2);
                if (!$this->_model->hasField($field)) {
                    throw new InvalidArgumentException(['`:field` field is not exist in `:collection` collection',
                        'field' => $field, 'collection' => $this->_model->getSource()
                    ]);
                }

                if (is_scalar($value)) {
                    if (is_int($value)) {
                        $this->_filters[] = [$field => ['$in' => [(string)$value, (int)($value)]]];
                    } elseif (is_float($value)) {
                        $this->_filters[] = [$field => ['$in' => [(string)$value, (double)$value]]];
                    } else {
                        $this->_filters[] = [$field => ['$in' => [(string)$value, (int)($value), (double)$value]]];
                    }
                } else {
                    throw new InvalidValueException(['`:filter` filter is not  valid: value must be scalar value', 'filter' => $filter]);
                }
            } elseif ($operator === '@=') {
                $field = substr($filter, 0, -2);
                $times = $this->_normalizeTimeBetween($field, $value);
                $this->whereBetween($field, $times[0], $times[1]);
            } elseif ($operator === '^=') {
                $this->whereStartsWith($field, $value);
            } elseif ($operator === '$=') {
                $this->whereEndsWith($field, $value);
            } elseif ($operator === '*=') {
                $this->whereContains($field, $value);
            } elseif ($operator === ',=') {
                $this->whereInset($field, $value);
            } else {
                $operator_map = ['>' => '$gt', '>=' => '$gte', '<' => '$lt', '<=' => '$lte', '!=' => '$ne', '<>' => '$ne'];
                if (!isset($operator_map[$operator])) {
                    throw new InvalidValueException(['unknown `:where` where filter', 'where' => $filter]);
                }
                $fieldTypes = $this->_model->getFieldTypes();
                $this->_filters[] = [$field => [$operator_map[$operator] => $this->_model->getNormalizedValue($fieldTypes[$field], $value)]];
            }
        } else {
            throw new InvalidValueException(['unknown mongodb criteria `filter` filter', 'filter' => $filter]);
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
     * @param string           $field
     * @param int|float|string $min
     * @param int|float|string $max
     *
     * @return static
     * @throws \ManaPHP\Mongodb\Model\Exception
     */
    public function whereBetween($field, $min, $max)
    {
        if ($min === null || $min === '') {
            return $max === null || $max === '' ? $this : $this->where($field . '<=', $max);
        } elseif ($max === null || $max === '') {
            return $min === null || $min === '' ? $this : $this->where($field . '>=', $min);
        }

        $fieldTypes = $this->_model->getFieldTypes();
        $fieldType = $fieldTypes[$field];

        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $min = $this->_model->getNormalizedValue($fieldType, $min);
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $max = $this->_model->getNormalizedValue($fieldType, $max);

        $this->_filters[] = [$field => ['$gte' => $min, '$lte' => $max]];

        return $this;
    }

    /**
     * Appends a NOT BETWEEN condition to the current conditions
     *
     *<code>
     *    $builder->notBetweenWhere('price', 100.25, 200.50);
     *</code>
     *
     * @param string           $field
     * @param int|float|string $min
     * @param int|float|string $max
     *
     * @return static
     * @throws \ManaPHP\Mongodb\Model\Exception
     */
    public function whereNotBetween($field, $min, $max)
    {
        if ($min === null || $min === '') {
            return $max === null || $max === '' ? $this : $this->where($field . '>', $max);
        } elseif ($max === null || $max === '') {
            return $min === null || $min === '' ? $this : $this->where($field . '<', $min);
        }

        $fieldTypes = $this->_model->getFieldTypes();
        $fieldType = $fieldTypes[$field];

        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $min = $this->_model->getNormalizedValue($fieldType, $min);
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $max = $this->_model->getNormalizedValue($fieldType, $max);

        $this->_filters[] = ['$or' => [[$field => ['$lt' => $min]], [$field => ['$gt' => $max]]]];

        return $this;
    }

    /**
     * Appends an IN condition to the current conditions
     *
     *<code>
     *    $builder->inWhere('id', [1, 2, 3]);
     *</code>
     *
     * @param string $field
     * @param array  $values
     *
     * @return static
     * @throws \ManaPHP\Mongodb\Model\Exception
     */
    public function whereIn($field, $values)
    {
        $fieldTypes = $this->_model->getFieldTypes();
        $fieldType = $fieldTypes[$field];

        $map = ['integer' => 'intval', 'double' => 'floatval', 'string' => 'strval', 'boolean' => 'boolval'];
        if (isset($map[$fieldType])) {
            $values = array_map($map[$fieldType], $values);
        } else {
            foreach ($values as $k => $value) {
                $values[$k] = $this->_model->getNormalizedValue($fieldType, $value);
            }
        }

        $this->_filters[] = [$field => ['$in' => $values]];

        return $this;
    }

    /**
     * Appends a NOT IN condition to the current conditions
     *
     *<code>
     *    $builder->notInWhere('id', [1, 2, 3]);
     *</code>
     *
     * @param string $field
     * @param array  $values
     *
     * @return static
     * @throws \ManaPHP\Mongodb\Model\Exception
     */
    public function whereNotIn($field, $values)
    {
        $fieldTypes = $this->_model->getFieldTypes();
        $fieldType = $fieldTypes[$field];

        $map = ['integer' => 'intval', 'double' => 'floatval', 'string' => 'strval', 'boolean' => 'boolval'];
        if (isset($map[$fieldType])) {
            $values = array_map($map[$fieldType], $values);
        } else {
            foreach ($values as $k => $value) {
                $values[$k] = $this->_model->getNormalizedValue($fieldType, $value);
            }
        }

        $this->_filters[] = [$field => ['$nin' => $values]];

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
        return $this->whereRegex($field, '\b' . $value . '\b');
    }

    /**
     * @param string $field
     * @param string $value
     *
     * @return static
     */
    public function whereNotInset($field, $value)
    {
        return $this->whereNotRegex($field, '\b' . $value . '\b');
    }

    /**
     * @param string|array $expr
     * @param string       $like
     *
     * @return static
     */
    protected function _whereLike($expr, $like)
    {
        if ($like === '') {
            return $this;
        }

        if (is_array($expr)) {
            $or = [];
            /** @noinspection ForeachSourceInspection */
            foreach ($expr as $v) {
                $or[] = [$v => ['$regex' => $like, '$options' => 'i']];
            }
            $this->_filters[] = ['$or' => $or];
        } else {
            $this->_filters[] = [$expr => ['$regex' => $like, '$options' => 'i']];
        }

        return $this;
    }

    /**
     * @param string|array $expr
     * @param string       $like
     *
     * @return static
     */
    protected function _whereNotLike($expr, $like)
    {
        if ($like === '') {
            return $this;
        }

        if (is_array($expr)) {
            $and = [];
            /** @noinspection ForeachSourceInspection */
            foreach ($expr as $v) {
                $and[] = [$v => ['$not' => new Regex($like, 'i')]];
            }
            $this->_filters[] = ['$and' => $and];
        } else {
            $this->_filters[] = [$expr => ['$not' => new Regex($like, 'i')]];
        }

        return $this;
    }

    /**
     * @param string|array $field
     * @param string       $value
     *
     * @return static
     */
    public function whereContains($field, $value)
    {
        return $value === '' ? $this : $this->_whereLike($field, $value);
    }

    /**
     * @param string|array $field
     * @param string       $value
     *
     * @return static
     */
    public function whereNotContains($field, $value)
    {
        return $value === '' ? $this : $this->_whereNotLike($field, $value);
    }

    /**
     * @param string|array $field
     * @param string       $value
     * @param int          $length
     *
     * @return static
     */
    public function whereStartsWith($field, $value, $length = null)
    {
        if ($value === '') {
            return $this;
        }

        if ($length === null) {
            return $this->_whereLike($field, '^' . $value);
        } else {
            return $this->_whereLike($field, '^' . str_pad($value, $length, '.') . '$');
        }
    }

    /**
     * @param string|array $field
     * @param string       $value
     * @param int          $length
     *
     * @return static
     */
    public function whereNotStartsWith($field, $value, $length = null)
    {
        if ($value === '') {
            return $this;
        }

        if ($length === null) {
            return $this->_whereNotLike($field, '^' . $value);
        } else {
            return $this->_whereNotLike($field, '^' . str_pad($value, $length, '.') . '$');
        }
    }

    /**
     * @param string|array $field
     * @param string       $value
     *
     * @return static
     */
    public function whereEndsWith($field, $value)
    {
        return $value === '' ? $this : $this->_whereLike($field, $value . '$');
    }

    /**
     * @param string|array $field
     * @param string       $value
     *
     * @return static
     */
    public function whereNotEndsWith($field, $value)
    {
        return $value === '' ? $this : $this->_whereNotLike($field, $value . '$');
    }

    /**
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     */
    public function whereLike($expr, $value)
    {
        if ($value === '') {
            return $this;
        }

        if ($value[0] !== '%') {
            $value = '^' . $value;
        }

        if ($value[strlen($value) - 1] !== '%') {
            $value .= '$';
        }

        $value = strtr($value, ['%' => '.*', '_' => '.']);

        return $this->_whereLike($expr, $value);
    }

    /**
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     */
    public function whereNotLike($expr, $value)
    {
        if ($value === '') {
            return $this;
        }

        if ($value[0] !== '%') {
            $value = '^' . $value;
        }

        if ($value[strlen($value) - 1] !== '%') {
            $value .= '$';
        }

        $value = strtr($value, ['%' => '.*', '_' => '.']);

        return $this->_whereNotLike($expr, $value);
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
        $this->_filters[] = [$field => ['$regex' => $regex, '$options' => $flags]];

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
        $this->_filters[] = [$field => ['$not' => new Regex($regex, $flags)]];

        return $this;
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
                    throw new CriteriaException(['unknown `:order` order by for `:model` model', 'order' => $orderBy, 'model' => get_class($this->_model)]);
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
                            throw new CriteriaException(['`:group` substr index is 1-based', 'group' => $groupBy]);
                        }
                        $this->_group[$parts[0]] = ['$substr' => ['$' . $parts[0], $parts[1] - 1, (int)$parts[2]]];
                    }
                } else {
                    throw new CriteriaException(['`:group` group is not supported. ', 'group' => $groupBy]);
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
        $source = $this->_model->getSource();
        /**
         * @var \ManaPHP\MongodbInterface $db
         */
        $db = $this->_di->getShared($this->_model->getDb());
        if (!$this->_aggregate) {
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
        } else {
            $pipeline = [];
            if ($this->_filters) {
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

            $r = $db->aggregate($source, $pipeline);

            if ($this->_group !== null) {
                foreach ($r as $k => $row) {
                    if ($row['_id'] !== null) {
                        $row += $row['_id'];
                    }
                    unset($row['_id']);
                    $r[$k] = $row;
                }
            }
        }

        if ($this->_index === null) {
            return $r;
        }

        $indexBy = $this->_index;
        if (is_scalar($indexBy)) {
            $rows = [];
            foreach ($r as $row) {
                $rows[$row[$indexBy]] = $row;
            }
        } elseif (is_array($indexBy)) {
            $k = key($indexBy);
            $v = current($indexBy);

            $rows = [];
            foreach ($r as $row) {
                $rows[$row[$k]] = $row[$v];
            }
        } else {
            $rows = [];
            foreach ($r as $row) {
                $rows[$indexBy($row)] = $row;
            }
        }

        return $rows;
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
     */
    public function paginate($size = null, $page = null)
    {
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
        return $this->paginator->paginate($count, $this->_limit, (int)($this->_offset / $this->_limit) + 1);
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
     * @return bool
     */
    public function exists()
    {
        return $this->select(['_id'])->fetchOne() !== false;
    }

    /**
     * @return int
     */
    public function delete()
    {
        $db = $this->_model->getDb($this);
        $source = $this->_model->getSource($this);

        return $this->_di->getShared($db)->delete($source, $this->_filters ? ['$and' => $this->_filters] : []);
    }

    /**
     * @param $fieldValues
     *
     * @return int
     */
    public function update($fieldValues)
    {
        $db = $this->_model->getDb($this);
        $source = $this->_model->getSource($this);

        return $this->_di->getShared($db)->update($source, $fieldValues, $this->_filters ? ['$and' => $this->_filters] : []);
    }
}
