<?php
namespace ManaPHP\Model;

interface CriteriaInterface
{
    /**
     * @param string|array $fields
     *
     * @return static
     */
    public function select($fields);

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
     * @param array $fields
     *
     * @return static
     */
    public function whereRequest($fields);

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
     * @param string|array $field
     * @param string       $value
     *
     * @return static
     */
    public function whereContains($field, $value);

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
     *
     * @return static
     */
    public function whereEndsWith($field, $value);

    /**
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     */
    public function whereLike($expr, $value);

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
    public function page($size, $page = null);

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
     * @param array|int $options
     *
     * @return static
     */
    public function cache($options);

    /**
     * @param bool $multiple
     *
     * @return static
     */
    public function setFetchType($multiple);

    /**
     * @return \ManaPHP\Model[]|\ManaPHP\Model
     */
    public function fetch();

    /**
     * @param string $field
     *
     * @return array
     */
    public function distinctField($field);

    /**
     * @param string $field
     *
     * @return int
     */
    public function count($field = null);

    /**
     *
     * @param string $field
     *
     * @return int|float
     */
    public function sum($field);

    /**
     * @param string $field
     *
     * @return int|float
     */
    public function max($field);

    /**
     * @param string $field
     *
     * @return int|float
     */
    public function min($field);

    /**
     * @param string $field
     *
     * @return double
     */
    public function avg($field);

    /**
     * @param int $size
     * @param int $page
     *
     * @return \ManaPHP\Paginator
     */
    public function paginate($size, $page = null);

    /**
     * @return bool
     */
    public function exists();

    /**
     * @return array
     */
    public function execute();

    /**
     * @return \ManaPHP\ModelInterface|false
     */
    public function fetchOne();

    /**
     * @return array|\ManaPHP\ModelInterface[]
     */
    public function fetchAll();

    /**
     * @param $fieldValues
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