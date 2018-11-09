<?php

namespace ManaPHP\Db;

interface QueryInterface extends \ManaPHP\QueryInterface
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
     * @param string $filter
     * @param array  $bind
     *
     * @return static
     */
    public function whereRaw($filter, $bind = null);

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
     * @param \ManaPHP\Db\QueryInterface[] $queries
     * @param bool                         $distinct
     *
     * @return static
     */
    public function union($queries, $distinct = false);
}