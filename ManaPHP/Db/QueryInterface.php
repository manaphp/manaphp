<?php
namespace ManaPHP\Db;

interface QueryInterface
{
    /**
     * @param \ManaPHP\DbInterface|string $db
     *
     * @return static
     */
    public function setDb($db);

    /**
     * Sets SELECT DISTINCT / SELECT ALL flag
     *
     * @param bool $distinct
     *
     * @return static
     */
    public function distinct($distinct = true);

    /**
     * @param string|array $columns
     *
     * @return static
     */
    public function select($columns);

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
    public function join($table, $condition = null, $alias = null, $type = null);

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
    public function innerJoin($table, $condition = null, $alias = null);

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
    public function leftJoin($table, $condition = null, $alias = null);

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
    public function rightJoin($table, $condition = null, $alias = null);

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
     * @param string $filter
     * @param array  $bind
     *
     * @return static
     */
    public function whereRaw($filter, $bind = null);

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
    public function whereStartsWith($expr, $value);

    /**
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     */
    public function whereEndsWith($expr, $value);

    /**
     * @param string|array $expr
     * @param string       $like
     *
     * @return static
     */
    public function whereLike($expr, $like);

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
    public function having($having, $bind = []);

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
    public function forUpdate($forUpdate = true);

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
     * @param callable|string $indexBy
     *
     * @return static
     */
    public function indexBy($indexBy);

    /**
     * @return string
     */
    public function getSql();

    /**
     * @param string $key
     *
     * @return array
     */
    public function getBind($key = null);

    /**
     * Set default bind parameters
     *
     * @param array $bind
     * @param bool  $merge
     *
     * @return static
     */
    public function setBind($bind, $merge = true);

    /**
     * @return array
     */
    public function getTables();

    /**
     * @param array|int $options
     *
     * @return static
     */
    public function cache($options);

    /**
     * @param \ManaPHP\Db\QueryInterface[] $queries
     * @param bool                         $distinct
     *
     * @return static
     */
    public function union($queries, $distinct = false);

    /**
     * @param bool $forceUseMaster
     *
     * @return static
     */
    public function forceUseMaster($forceUseMaster = true);

    /**
     *
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
     * @return \ManaPHP\PaginatorInterface
     */
    public function paginate($size, $page = null);

    /**
     * @return bool
     */
    public function exists();

    /**
     * @param string $column
     *
     * @return int
     */
    public function count($column = '*');

    /**
     * @return array|false
     */
    public function fetchOne();

    /**
     * @return array
     */
    public function fetchAll();

    /**
     * @param string $field
     *
     * @return array
     */
    public function distinctField($field);

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