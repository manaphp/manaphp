<?php
namespace ManaPHP\Model;

interface CriteriaInterface
{
    /**
     * @return \ManaPHP\Model
     */
    public function getModel();

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
     * @param array $expr
     *
     * @return array
     */
    public function aggregate($expr);

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
    public function where($filter, $value = null);

    /**
     * @param array $filters
     *
     * @return static
     */
    public function whereSearch($filters);

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
     */
    public function whereBetween($field, $min, $max);

    /**
     * @param string     $field
     * @param int|string $min
     * @param int|string $max
     *
     * @return static
     */
    public function whereDateBetween($field, $min, $max);

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
     */
    public function whereNotBetween($field, $min, $max);

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
     */
    public function whereIn($field, $values);

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
     */
    public function whereNotIn($field, $values);

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
     * @param string|array $field
     * @param string       $value
     *
     * @return static
     */
    public function whereContains($field, $value);

    /**
     * @param string|array $field
     * @param string       $value
     *
     * @return static
     */
    public function whereNotContains($field, $value);

    /**
     * @param string|array $field
     * @param string       $value
     * @param int          $length
     *
     * @return static
     */
    public function whereStartsWith($field, $value, $length = null);

    /**
     * @param string|array $field
     * @param string       $value
     * @param int          $length
     *
     * @return static
     */
    public function whereNotStartsWith($field, $value, $length = null);

    /**
     * @param string|array $field
     * @param string       $value
     *
     * @return static
     */
    public function whereEndsWith($field, $value);

    /**
     * @param string|array $field
     * @param string       $value
     *
     * @return static
     */
    public function whereNotEndsWith($field, $value);

    /**
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     */
    public function whereLike($expr, $value);

    /**
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     */
    public function whereNotLike($expr, $value);

    /**
     * @param string $field
     * @param string $regex
     * @param string $flags
     *
     * @return static
     */
    public function whereRegex($field, $regex, $flags = '');

    /**
     * @param string $field
     * @param string $regex
     * @param string $flags
     *
     * @return static
     */
    public function whereNotRegex($field, $regex, $flags = '');

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
    public function orderBy($orderBy);

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
    public function limit($limit, $offset = null);

    /**
     * @param int $size
     * @param int $page
     *
     * @return static
     */
    public function page($size = null, $page = null);

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
    public function groupBy($groupBy);

    /**
     * @param callable|string|array $indexBy
     *
     * @return static
     */
    public function indexBy($indexBy);

    /**
     * @param bool $multiple
     *
     * @return static
     */
    public function setFetchType($multiple);

    /**
     * @param bool $asArray
     *
     * @return \ManaPHP\Model[]|\ManaPHP\Model|array
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
     * @param string $field
     *
     * @return int
     */
    public function count($field = '*');

    /**
     *
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
     * @return double|null
     */
    public function avg($field);

    /**
     * @param int $size
     * @param int $page
     *
     * @return \ManaPHP\Paginator
     */
    public function paginate($size = null, $page = null);

    /**
     * @return bool
     */
    public function exists();

    /**
     * @param array $fieldValues
     *
     * @return int
     */
    public function update($fieldValues);

    /**
     * @return int
     */
    public function delete();

    /**
     * @param bool $forceUseMaster
     *
     * @return static
     */
    public function forceUseMaster($forceUseMaster = true);
}