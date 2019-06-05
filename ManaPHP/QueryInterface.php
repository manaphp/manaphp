<?php
namespace ManaPHP;

interface QueryInterface
{
    /**
     * @param mixed|string $db
     *
     * @return static
     */
    public function setDb($db);

    /**
     * @param string|\ManaPHP\Model $model
     *
     * @return static
     */
    public function setModel($model);

    /**
     * @return \ManaPHP\Model
     */
    public function getModel();

    /**
     * @param string $table
     * @param string $alias
     *
     * @return static
     */
    public function from($table, $alias = null);

    /**
     * @param string|array $fields
     *
     * @return static
     */
    public function select($fields);

    /**
     * Sets SELECT DISTINCT / SELECT ALL flag
     *
     * @param bool $distinct
     *
     * @return static
     */
    public function distinct($distinct = true);

    /**
     * Appends a condition to the current conditions using a AND operator
     *
     * @param string|array           $filter
     * @param int|float|string|array $value
     *
     * @return static
     */
    public function where($filter, $value = null);

    /**
     * @param string $field
     * @param mixed  $value
     *
     * @return static
     */
    public function whereEq($field, $value);

    /**
     * @param string $field
     * @param string $operator
     * @param mixed  $value
     *
     * @return static
     */
    public function whereCmp($field, $operator, $value);

    /**
     * @param string $field
     * @param int    $divisor
     * @param int    $remainder
     *
     * @return static
     */
    public function whereMod($field, $divisor, $remainder);

    /**
     * @param string $expr
     * @param array  $bind
     *
     * @return static
     */
    public function whereExpr($expr, $bind = null);

    /**
     * @param array $filters
     *
     * @return static
     */
    public function whereInput($filters);

    /**
     * Appends a BETWEEN condition to the current conditions
     *
     * @param string           $expr
     * @param int|float|string $min
     * @param int|float|string $max
     *
     * @return static
     */
    public function whereBetween($expr, $min, $max);

    /**
     * Appends a NOT BETWEEN condition to the current conditions
     *
     * @param string           $expr
     * @param int|float|string $min
     * @param int|float|string $max
     *
     * @return static
     */
    public function whereNotBetween($expr, $min, $max);

    /**
     * @param string     $field
     * @param int|string $min
     * @param int|string $max
     *
     * @return static
     */
    public function whereDateBetween($field, $min, $max);

    /**
     * Appends an IN condition to the current conditions
     *
     * @param string                           $expr
     * @param array|\ManaPHP\Db\QueryInterface $values
     *
     * @return static
     */
    public function whereIn($expr, $values);

    /**
     * Appends a NOT IN condition to the current conditions
     *
     * @param string                           $expr
     * @param array|\ManaPHP\Db\QueryInterface $values
     *
     * @return static
     */
    public function whereNotIn($expr, $values);

    /**
     * @param string $field
     * @param string $value
     *
     * @return static
     */
    public function whereInset($field, $value);

    /**
     * @param string $field
     * @param string $value
     *
     * @return static
     */
    public function whereNotInset($field, $value);

    /**
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     */
    public function whereContains($expr, $value);

    /**
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     */
    public function whereNotContains($expr, $value);

    /**
     * @param string|array $expr
     * @param string       $value
     * @param int          $length
     *
     * @return static
     */
    public function whereStartsWith($expr, $value, $length = null);

    /**
     * @param string|array $expr
     * @param string       $value
     * @param int          $length
     *
     * @return static
     */
    public function whereNotStartsWith($expr, $value, $length = null);

    /**
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     */
    public function whereEndsWith($expr, $value);

    /**
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     */
    public function whereNotEndsWith($expr, $value);

    /**
     * @param string|array $expr
     * @param string       $like
     *
     * @return static
     */
    public function whereLike($expr, $like);

    /**
     * @param string|array $expr
     * @param string       $like
     *
     * @return static
     */
    public function whereNotLike($expr, $like);

    /**
     * @param string $expr
     * @param string $regex
     * @param string $flags
     *
     * @return static
     */
    public function whereRegex($expr, $regex, $flags = '');

    /**
     * @param string $expr
     * @param string $regex
     * @param string $flags
     *
     * @return static
     */
    public function whereNotRegex($expr, $regex, $flags = '');

    /**
     * @param string $expr
     *
     * @return static
     */
    public function whereNull($expr);

    /**
     * @param string $expr
     *
     * @return static
     */
    public function whereNotNull($expr);

    /**
     * Sets a ORDER BY condition clause
     *
     * @param string|array $orderBy
     *
     * @return static
     */
    public function orderBy($orderBy);

    /**
     * @param callable|string|array $indexBy
     *
     * @return static
     */
    public function indexBy($indexBy);

    /**
     * Sets a GROUP BY clause
     *
     * @param string|array $groupBy
     *
     * @return static
     */
    public function groupBy($groupBy);

    /**
     * @param array $options
     *
     * @return static
     */
    public function options($options);

    /**
     * @param string|array $with
     *
     * @return static
     */
    public function with($with);

    /**
     * Sets a LIMIT clause, optionally a offset clause
     *
     * @param int $limit
     * @param int $offset
     *
     * @return static
     */
    public function limit($limit, $offset = null);

    /**
     * @param int $size
     * @param int $page
     *
     * @return static
     */
    public function page($size = null, $page = null);

    /**
     * @param bool $forceUseMaster
     *
     * @return static
     */
    public function forceUseMaster($forceUseMaster = true);

    /**
     * @return array
     */
    public function execute();

    /**
     * @param array $expr
     *
     * @return array
     */
    public function aggregate($expr);

    /**
     * @param int $size
     * @param int $page
     *
     * @return \ManaPHP\Paginator
     */
    public function paginate($size = null, $page = null);

    /**
     * @param bool $multiple
     *
     * @return static
     */
    public function setFetchType($multiple);

    /**
     * @param bool $asArray
     *
     * @return \ManaPHP\Model[]|\ManaPHP\Model|array|null
     */
    public function fetch($asArray = false);

    /**
     * @param string|array $fields
     *
     * @return array|null
     */
    public function first($fields = null);

    /**
     * @param string|array $fields
     *
     * @return array
     */
    public function get($fields = null);

    /**
     * @param string|array $fields
     *
     * @return array
     */
    public function all($fields = null);

    /**
     * @param string $field
     * @param mixed  $default
     *
     * @return mixed
     */
    public function value($field, $default = null);

    /**
     * @param string $field
     *
     * @return array
     */
    public function values($field);

    /**
     * @return bool
     */
    public function exists();

    /**
     * @param string $field
     *
     * @return int
     */
    public function count($field = '*');

    /**
     * @param string $field
     *
     * @return int|float|null
     */
    public function sum($field);

    /**
     * @param string $field
     *
     * @return int|float|null
     */
    public function max($field);

    /**
     * @param string $field
     *
     * @return int|float|null
     */
    public function min($field);

    /**
     * @param string $field
     *
     * @return float|null
     */
    public function avg($field);

    /**
     * @return int
     */
    public function delete();

    /**
     * @param array $fieldValues
     *
     * @return int
     */
    public function update($fieldValues);

    /**
     * @param mixed    $value
     * @param callable $true_call
     * @param callable $false_call
     *
     * @return static
     */
    public function when($value, $true_call, $false_call = null);

    /**
     * @param string     $field
     * @param string|int $date
     * @param string     $format
     *
     * @return static
     */
    public function whereDate($field, $date, $format = null);

    /**
     * @param string     $field
     * @param string|int $date
     * @param string     $format
     *
     * @return static
     */
    public function whereMonth($field, $date, $format = null);

    /**
     * @param string $id
     * @param string $value
     *
     * @return static
     */
    public function where1v1($id, $value);
}