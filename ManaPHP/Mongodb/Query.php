<?php
namespace ManaPHP\Mongodb;

use ManaPHP\Di;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\InvalidFormatException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Model\Expression\Decrement;
use ManaPHP\Model\Expression\Increment;
use ManaPHP\Model\ExpressionInterface;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;

class Query extends \ManaPHP\Query
{
    /**
     * @var \ManaPHP\MongodbInterface|string
     */
    protected $_db;

    /**
     * @var string
     */
    protected $_source;

    /**
     * @var array
     */
    protected $_types;

    /**
     * @var array
     */
    protected $_projection;

    /**
     * @var array
     */
    protected $_projection_alias;
    /**
     * @var array
     */
    protected $_aggregate = [];

    /**
     * @var array
     */
    protected $_filters = [];

    /**
     * @var array
     */
    protected $_group;

    /**
     * @var string|callable
     */
    protected $_index;

    /**
     * Query constructor.
     *
     * @param \ManaPHP\MongodbInterface|string $db
     */
    public function __construct($db = null)
    {
        $this->_db = $db;
    }

    /**
     * @param \ManaPHP\MongodbInterface|string $db
     *
     * @return static
     */
    public function setDb($db)
    {
        $this->_db = $db;

        return $this;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        if ($this->_model) {
            return $this->_model->getSource();
        } else {
            return $this->_source;
        }
    }

    /**
     * @param \ManaPHP\Model $model
     *
     * @return static
     */
    public function setModel($model)
    {
        $this->_model = $model;

        $this->setTypes($model->getFieldTypes());

        return $this;
    }

    /**
     * @param array $types
     *
     * @return static
     */
    public function setTypes($types)
    {
        $this->_types = $types;

        return $this;
    }

    /**
     * Sets SELECT DISTINCT / SELECT ALL flag
     *
     * @param string $field
     *
     * @return array
     */
    public function values($field)
    {
        $source = $this->getSource();
        if ($pos = strpos($source, '.')) {
            $db = substr($source, 0, $source);
            $collection = substr($source, $pos + 1);
        } else {
            $db = null;
            $collection = $source;
        }

        $mongodb = $this->getConnection();

        $cmd = ['distinct' => $collection, 'key' => $field];
        if ($this->_filters) {
            $filters = [];
            foreach ($this->_filters as $filter) {
                $key = key($filter);
                $value = current($filter);
                if (isset($filters[$key]) || count($filter) !== 1) {
                    $filters = ['$and' => $this->_filters];
                    break;
                }
                $filters[$key] = $value;
            }
            $cmd['query'] = $filters;
        }

        $r = $mongodb->command($cmd, $db)[0];
        if (!$r['ok']) {
            throw new InvalidFormatException([
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
        if (!$fields) {
            return $this;
        }

        if (is_string($fields)) {
            $fields = (array)preg_split('#[\s,]+#', $fields, -1, PREG_SPLIT_NO_EMPTY);
        }

        if ($fields) {
            $this->_projection_alias = [];

            if (isset($fields[count($fields) - 1])) {
                $this->_projection = array_fill_keys($fields, 1);
            } else {
                $projection = [];
                foreach ($fields as $k => $v) {
                    if (is_int($k)) {
                        $projection[$v] = 1;
                    } else {
                        $this->_projection_alias[$k] = $v;
                        $projection[$v] = 1;
                    }
                }
                $this->_projection = $projection;
            }

            if (!isset($this->_projection['_id'])) {
                $this->_projection['_id'] = false;
            }
        }

        return $this;
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
            if (!$this->_model && strpos($table, '\\') !== false) {
                $this->_model = $this->_di->getShared($table);
            } else {
                $this->_source = $table;
            }
        }

        return $this;
    }

    /**
     * @param string $expr
     *
     * @return array
     */
    protected function _compileCondExpression($expr)
    {
        if (strpos($expr, ',') !== false) {
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
            $alg = ['=' => '$eq', '>' => '$gt', '>=' => '$gte', '<' => '$lt', '<=' => '$lte', '!=' => '$neq', '<>' => '$neq'];
            return ['$cond' => [[$alg[$op2] => [is_numeric($op1) ? (float)$op1 : '$' . $op1, is_numeric($op3) ? (float)$op3 : '$' . $op3]], $true, $false]];
        } else {
            return null;
        }
    }

    /**
     * @param array $expr
     *
     * @return array
     */
    public function aggregate($expr)
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
                $this->_aggregate[$k] = $v;
                continue;
            }

            if (preg_match('#^(\w+)\((.*)\)$#', $v, $match) !== 1) {
                throw new MisuseException(['`:aggregate` aggregate is invalid.', 'aggregate' => $v]);
            }

            $accumulator = strtolower($match[1]);
            $normalizes = ['group_concat' => 'push',
                'std' => 'stdDevPop',
                'stddev' => 'stdDevPop',
                'stddev_pop' => 'stdDevPop',
                'stddev_samp' => 'stdDevSamp',
                'addtoset' => 'addToSet',
                'stddevpop' => 'stdDevPop',
                'stddevsamp' => 'stdDevSamp'];
            if (isset($normalizes[$accumulator])) {
                $accumulator = $normalizes[$accumulator];
            }
            $operand = $match[2];
            if ($accumulator === 'count') {
                $this->_aggregate[$k] = ['$sum' => 1];
            } elseif ($accumulator === 'count_if') {
                if ($cond = $this->_compileCondExpression($operand)) {
                    $this->_aggregate[$k] = ['$sum' => $cond];
                } else {
                    throw new MisuseException(['unknown COUNT_IF expression: `:expression`', 'expression' => $operand]);
                }
            } elseif ($accumulator === 'sum_if') {
                if ($cond = $this->_compileCondExpression($operand)) {
                    $this->_aggregate[$k] = ['$sum' => $cond];
                } else {
                    throw new MisuseException(['unknown SUM_IF expression: `:expression`', 'expression' => $operand]);
                }
            } elseif ($accumulator === 'avg_if') {
                if ($cond = $this->_compileCondExpression($operand)) {
                    $this->_aggregate[$k] = ['$avg' => $cond];
                } else {
                    throw new MisuseException(['unknown AVG_IF expression: `:expression`', 'expression' => $operand]);
                }
            } elseif (in_array($accumulator, ['avg', 'first', 'last', 'max', 'min', 'push', 'addToSet', 'stdDevPop', 'stdDevSamp', 'sum'], true)) {
                if (preg_match('#^[\w.]+$#', $operand) === 1) {
                    $this->_aggregate[$k] = ['$' . $accumulator => '$' . $operand];
                } elseif (preg_match('#^([\w.]+)\s*([+\-*/%])\s*([\w.]+)$#', $operand, $match2) === 1) {
                    $operator_map = ['+' => '$add', '-' => '$subtract', '*' => '$multiply', '/' => '$divide', '%' => '$mod'];
                    $sub_operand = $operator_map[$match2[2]];
                    $sub_operand1 = is_numeric($match2[1]) ? (float)$match2[1] : ('$' . $match2[1]);
                    $sub_operand2 = is_numeric($match2[3]) ? (float)$match2[3] : ('$' . $match2[3]);
                    $this->_aggregate[$k] = ['$' . $accumulator => [$sub_operand => [$sub_operand1, $sub_operand2]]];
                } elseif ($cond = $this->_compileCondExpression($operand)) {
                    $this->_aggregate[$k] = ['$' . $accumulator => $this->_compileCondExpression($operand)];
                } else {
                    throw new MisuseException(['unknown `:operand` operand of `:aggregate` aggregate', 'operand' => $operand, 'aggregate' => $v]);
                }
            } else {
                throw new MisuseException([
                    'unknown `:accumulator` accumulator of `:aggregate` aggregate',
                    'accumulator' => $accumulator,
                    'aggregate' => $v
                ]);
            }
        }

        return $this->execute();
    }

    /**
     * @param string $field
     * @param mixed  $value
     *
     * @return bool|float|int|string|array|\MongoDB\BSON\ObjectID|\MongoDB\BSON\UTCDateTime
     */
    public function normalizeValue($field, $value)
    {
        if ($value === null || !$this->_types) {
            return $value;
        }

        if (!isset($this->_types[$field])) {
            throw new MisuseException(['`:field` field type is not defined', 'field' => $field]);
        }

        $type = $this->_types[$field];

        if ($type === 'string') {
            return is_string($value) ? $value : (string)$value;
        } elseif ($type === 'int') {
            return is_int($value) ? $value : (int)$value;
        } elseif ($type === 'float') {
            return is_float($value) ? $value : (float)$value;
        } elseif ($type === 'objectid') {
            return is_scalar($type) ? new ObjectId($value) : $value;
        } elseif ($type === 'bool') {
            return is_bool($value) ? $value : (bool)$value;
        } elseif ($type === 'array') {
            return (array)$value;
        } else {
            throw new InvalidValueException(['normalize `:type` type value is not supported', 'type' => $type]);
        }
    }

    /**
     * @param string $field
     * @param array  $values
     *
     * @return array
     */
    public function normalizeValues($field, $values)
    {
        if (!$this->_types) {
            return $values;
        }

        $type = $this->_types[$field];

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
                        $this->_filters[] = [$field => ['$in' => $value]];
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
                    $this->_filters[] = [$filter => $value];
                }
            } elseif (preg_match('#^([\w.]+)\s*([<>=!^$*~,@dm?]*)$#', $filter, $matches) === 1) {
                list(, $field, $operator) = $matches;

                if (strpos($operator, '?') !== false) {
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
                } else {
                    throw new MisuseException(['unknown `:operator` operator', 'operator' => $operator]);
                }
            } elseif (strpos($filter, ',') !== false && preg_match('#^[\w,.]+$#', $filter)) {
                $this->where1v1($filter, $value);
            } else {
                throw new InvalidValueException(['unknown mongodb query `filter` filter', 'filter' => $filter]);
            }
        }

        return $this;
    }

    /**
     * @param string $field
     * @param mixed  $value
     *
     * @return static
     */
    public function whereEq($field, $value)
    {
        $this->_filters[] = [$field => $this->normalizeValue($field, $value)];

        return $this;
    }

    /**
     * @param string $field
     * @param string $operator
     * @param mixed  $value
     *
     * @return static
     */
    public function whereCmp($field, $operator, $value)
    {
        if ($operator === '=') {
            return $this->whereEq($field, $value);
        } elseif ($operator === '~=') {
            if ($this->_types && !isset($this->_types[$field])) {
                throw new InvalidArgumentException(['`:field` field is not exist in `:collection` collection',
                    'field' => $field,
                    'collection' => $this->getSource()
                ]);
            }

            if (is_scalar($value)) {
                if (is_int($value)) {
                    $this->_filters[] = [$field => ['$in' => [(string)$value, (int)$value]]];
                } elseif (is_float($value)) {
                    $this->_filters[] = [$field => ['$in' => [(string)$value, (float)$value]]];
                } else {
                    $this->_filters[] = [$field => ['$in' => [(string)$value, (int)$value, (float)$value]]];
                }
            } else {
                throw new InvalidValueException(['`:operator` operator is not  valid: value must be scalar value', 'operator' => $operator]);
            }
        } else {
            $operator_map = ['>' => '$gt', '>=' => '$gte', '<' => '$lt', '<=' => '$lte', '!=' => '$ne', '<>' => '$ne'];
            if (!isset($operator_map[$operator])) {
                throw new InvalidValueException(['unknown `:operator` operator', 'operator' => $operator]);
            }
            $this->_filters[] = [$field => [$operator_map[$operator] => $this->normalizeValue($field, $value)]];
        }

        return $this;
    }

    /**
     * @param string $field
     * @param int    $divisor
     * @param int    $remainder
     *
     * @return static
     */
    public function whereMod($field, $divisor, $remainder)
    {
        if (!is_int($divisor)) {
            throw new MisuseException('divisor must be an integer');
        }

        if (!is_int($remainder)) {
            throw new MisuseException('remainder must be an integer');
        }

        $this->_filters[] = [$field => ['$mod' => [$divisor, $remainder]]];

        return $this;
    }

    /**
     * @param string $expr
     * @param array  $bind
     *
     * @return static
     */
    public function whereExpr($expr, $bind = null)
    {
        $this->_filters[] = ['$where' => $expr];

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
     * @param string           $field
     * @param int|float|string $min
     * @param int|float|string $max
     *
     * @return static
     */
    public function whereBetween($field, $min, $max)
    {
        if ($min === null || $min === '') {
            return $max === null || $max === '' ? $this : $this->whereCmp($field, '<=', $max);
        } elseif ($max === null || $max === '') {
            return $min === null || $min === '' ? $this : $this->whereCmp($field, '>=', $min);
        }

        $this->_filters[] = [$field => ['$gte' => $this->normalizeValue($field, $min), '$lte' => $this->normalizeValue($field, $max)]];

        return $this;
    }

    /**
     * @param string           $field
     * @param int|float|string $min
     * @param int|float|string $max
     *
     * @return static
     */
    public function whereNotBetween($field, $min, $max)
    {
        if ($min === null || $min === '') {
            return $max === null || $max === '' ? $this : $this->whereCmp($field, '>', $max);
        } elseif ($max === null || $max === '') {
            return $min === null || $min === '' ? $this : $this->whereCmp($field, '<', $min);
        }

        $this->_filters[] = [$field => ['$not' => ['$gte' => $this->normalizeValue($field, $min), '$lte' => $this->normalizeValue($field, $max)]]];

        return $this;
    }

    /**
     * @param string $field
     * @param array  $values
     *
     * @return static
     */
    public function whereIn($field, $values)
    {
        $this->_filters[] = [$field => ['$in' => $this->normalizeValues($field, $values)]];

        return $this;
    }

    /**
     * @param string $field
     * @param array  $values
     *
     * @return static
     */
    public function whereNotIn($field, $values)
    {
        $this->_filters[] = [$field => ['$nin' => $this->normalizeValues($field, $values)]];

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
     * @param string $id
     * @param string $value
     *
     * @return static
     */
    public function where1v1($id, $value)
    {
        list($id_a, $id_b) = explode(',', $id);

        if (($pos = strpos($value, ',')) === false) {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $value = $this->normalizeValue($id_a, $value);
            $this->_filters[] = ['$or' => [[$id_a => $value], [$id_b => $value]]];
        } else {
            $value_a = $this->normalizeValue($id_a, substr($value, 0, $pos));
            $value_b = $this->normalizeValue($id_b, substr($value, $pos + 1));
            $this->_filters[] = ['$or' => [[$id_a => $value_a, $id_b => $value_b], [$id_a => $value_b, $id_b => $value_a]]];
        }

        return $this;
    }

    /**
     * @param string|array $groupBy
     *
     * @return static
     */
    public function groupBy($groupBy)
    {
        if (is_string($groupBy)) {
            if (strpos($groupBy, '(') !== false) {
                if (preg_match('#^([\w.]+)\((.*)\)$#', $groupBy, $match) === 1) {
                    $func = strtoupper($match[1]);
                    if ($func === 'SUBSTR') {
                        $parts = explode(',', $match[2]);

                        if ($parts[1] === '0') {
                            throw new MisuseException(['`:group` substr index is 1-based', 'group' => $groupBy]);
                        }
                        $this->_group[$parts[0]] = ['$substr' => ['$' . $parts[0], $parts[1] - 1, (int)$parts[2]]];
                    }
                } else {
                    throw new MisuseException(['`:group` group is not supported. ', 'group' => $groupBy]);
                }
            } else {
                foreach (explode(',', str_replace(' ', '', $groupBy)) as $field) {
                    $this->_group[$field] = '$' . $field;
                }
            }
        } elseif (is_array($groupBy)) {
            foreach ($groupBy as $k => $v) {
                if (is_int($k)) {
                    $this->_group[$v] = '$' . $v;
                } else {
                    $this->_group[$k] = $v;
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
        $this->_index = $indexBy;

        return $this;
    }

    /**
     * @param array $order
     *
     * @return array
     */
    protected function _buildOrder($order)
    {
        $r = [];

        foreach ($order as $field => $type) {
            $r[$field] = $type === SORT_ASC ? 1 : -1;
        }

        return $r;
    }

    /**
     * @return \ManaPHP\MongodbInterface
     */
    public function getConnection()
    {
        if (!$this->_di) {
            $this->_di = Di::getDefault();
        }

        if (is_object($this->_db)) {
            return $this->_db;
        } elseif ($this->_model) {
            return $this->_di->getShared($this->_model->getDb());
        } else {
            return $this->_di->getShared($this->_db ?: 'mongodb');
        }
    }

    /**
     * @return array
     */
    public function execute()
    {
        $mongodb = $this->getConnection();
        if (!$this->_aggregate) {
            $options = [];

            if ($this->_projection) {
                if (isset($this->_projection['*'])) {
                    $options['projection'] = ['_id' => false];
                } else {
                    $options['projection'] = $this->_projection;
                }
            } elseif ($this->_model) {
                $options['projection'] = array_fill_keys($this->_model->getFields(), 1);
            }

            if (isset($options['projection']) && !isset($options['projection']['_id'])) {
                if ($this->_model) {
                    if ($this->_model->getPrimaryKey() !== '_id') {
                        $options['projection']['_id'] = false;
                    }
                } else {
                    $options['projection']['_id'] = false;
                }
            }

            if ($this->_order) {
                $options['sort'] = $this->_buildOrder($this->_order);
            }

            if ($this->_offset !== null) {
                $options['skip'] = $this->_offset;
            }

            if ($this->_limit !== null) {
                $options['limit'] = $this->_limit;
            }

            $filters = [];
            foreach ($this->_filters as $filter) {
                $key = key($filter);
                $value = current($filter);
                if (isset($filters[$key]) || count($filter) !== 1) {
                    $filters = ['$and' => $this->_filters];
                    break;
                }
                $filters[$key] = $value;
            }

            $r = $mongodb->fetchAll($this->getSource(), $filters, $options, !$this->_force_master);
            if ($this->_projection_alias) {
                foreach ($r as $k => $v) {
                    foreach ($this->_projection_alias as $ak => $av) {
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
            if ($this->_filters) {
                $pipeline[] = ['$match' => ['$and' => $this->_filters]];
            }

            $pipeline[] = ['$group' => ['_id' => $this->_group] + $this->_aggregate];

            if ($this->_order) {
                $pipeline[] = ['$sort' => $this->_buildOrder($this->_order)];
            }

            if ($this->_offset !== null) {
                $pipeline[] = ['$skip' => $this->_offset];
            }

            if ($this->_limit !== null) {
                $pipeline[] = ['$limit' => $this->_limit];
            }

            $r = $mongodb->aggregate($this->getSource(), $pipeline);

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
     * @param string $field
     *
     * @return int
     */
    public function count($field = '*')
    {
        $copy = clone $this;

        $copy->_limit = null;
        $copy->_offset = null;
        $copy->_order = null;
        $copy->_aggregate['count'] = ['$sum' => 1];
        $r = $copy->execute();

        return $r ? $r[0]['count'] : 0;
    }

    /**
     * @return bool
     */
    public function exists()
    {
        return (bool)$this->select(['_id'])->limit(1)->execute();
    }

    /**
     * @return int
     */
    public function delete()
    {
        $filters = [];
        foreach ($this->_filters as $filter) {
            $key = key($filter);
            $value = current($filter);
            if (isset($filters[$key]) || count($filter) !== 1) {
                $filters = ['$and' => $this->_filters];
                break;
            }
            $filters[$key] = $value;
        }

        return $this->getConnection()->delete($this->getSource(), $filters);
    }

    /**
     * @param array $fieldValues
     *
     * @return int
     */
    public function update($fieldValues)
    {
        $filters = [];
        foreach ($this->_filters as $filter) {
            $key = key($filter);
            $value = current($filter);
            if (isset($filters[$key]) || count($filter) !== 1) {
                $filters = ['$and' => $this->_filters];
                break;
            }
            $filters[$key] = $value;
        }

        $expressions = [];
        foreach ($fieldValues as $field => $value) {
            if ($value instanceof ExpressionInterface) {
                $expressions[$field] = $value;
                unset($fieldValues[$field]);
            }
        }

        if ($expressions) {
            if ($fieldValues) {
                $fieldValues = ['$set' => $fieldValues];
            }

            foreach ($expressions as $field => $value) {
                if ($value instanceof Increment) {
                    $fieldValues['$inc'][$field] = $value->step;
                } elseif ($value instanceof Decrement) {
                    $fieldValues['$inc'][$field] = -$value->step;
                }
            }
        }

        return $this->getConnection()->update($this->getSource(), $fieldValues, $filters);
    }
}