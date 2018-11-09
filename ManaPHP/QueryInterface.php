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
    public function from($table, $alias = null);

    /**
     * @param string|array $fields
     *
     * @return static
     */
    public function select($fields);

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
    public function whereNotBetween($expr, $min, $max);

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
    public function whereIn($expr, $values);

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
     * @param callable|string|array $indexBy
     *
     * @return static
     */
    public function indexBy($indexBy);

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
     * @return int
     */
    public function delete();

    /**
     * @param array $fieldValues
     *
     * @return int
     */
    public function update($fieldValues);
}