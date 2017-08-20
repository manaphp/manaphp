<?php
namespace ManaPHP\Mvc\Model;

interface CriteriaInterface
{
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
     * @param array $expr
     *
     * @return static
     */
    public function aggregate($expr);

    /**
     * @param mixed $params
     *
     * @return static
     */
    public function buildFromArray($params);

    /**
     * Appends a condition to the current conditions using a AND operator
     *
     *<code>
     *    $builder->andWhere('name = "Peter"');
     *    $builder->andWhere('name = :name: AND id > :id:', array('name' => 'Peter', 'id' => 100));
     *</code>
     *
     * @param string|array           $condition
     * @param int|float|string|array $bind
     *
     * @return static
     */
    public function where($condition, $bind = []);

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
    public function betweenWhere($expr, $min, $max);

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
    public function notBetweenWhere($expr, $min, $max);

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
    public function inWhere($expr, $values);

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
    public function notInWhere($expr, $values);

    /**
     * @param string|array $expr
     * @param string       $like
     *
     * @return static
     */
    public function likeWhere($expr, $like);

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
    public function limit($limit, $offset = 0);

    /**
     * @param int $size
     * @param int $page
     *
     * @return static
     */
    public function page($size, $page = 1);

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
     * @throws \ManaPHP\Db\Query\Exception
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
     * @param array|int $options
     *
     * @return static
     */
    public function cache($options);

    /**
     *
     * @return array
     * @throws \ManaPHP\Db\Query\Exception
     */
    public function execute();

    /**
     * @return bool
     */
    public function exists();
}