<?php

namespace ManaPHP\Db\Model;

/**
 * Interface ManaPHP\Mvc\Model\QueryBuilderInterface
 *
 * @package queryBuilder
 */
interface QueryInterface extends \ManaPHP\Db\QueryInterface
{
    /**
     * Sets the columns to be queried
     *
     * @param string $fields
     *
     * @return static
     */
    public function columns($fields);

    /**
     * alias of addFrom
     *
     * @param string $model
     * @param string $alias
     *
     * @return static
     */
    public function from($model, $alias = null);

    /**
     * Add a model to take part of the query
     *
     * @param string|\ManaPHP\Db\Model\QueryInterface $model
     * @param string                                  $alias
     *
     * @return static
     */
    public function addFrom($model, $alias = null);

    /**
     * Adds a INNER join to the query
     *
     * @param string|\ManaPHP\Db\Model\QueryInterface $model
     * @param string                                  $condition
     * @param string                                  $alias
     * @param string                                  $type
     *
     * @return static
     */
    public function join($model, $condition = null, $alias = null, $type = null);

    /**
     * Adds a INNER join to the query
     *
     * @param string|\ManaPHP\Db\Model\QueryInterface $model
     * @param string                                  $condition
     * @param string                                  $alias
     *
     * @return static
     */
    public function innerJoin($model, $condition = null, $alias = null);

    /**
     * Adds a LEFT join to the query
     *
     * @param string|\ManaPHP\Db\Model\QueryInterface $model
     * @param string                                  $condition
     * @param string                                  $alias
     *
     * @return static
     */
    public function leftJoin($model, $condition = null, $alias = null);

    /**
     * Adds a RIGHT join to the query
     *
     * @param string|\ManaPHP\Db\Model\QueryInterface $model
     * @param string                                  $condition
     * @param string                                  $alias
     *
     * @return static
     */
    public function rightJoin($model, $condition = null, $alias = null);

    /**
     * Appends a condition to the current conditions using a AND operator
     *
     * @param string                 $condition
     * @param int|float|string|array $bind
     *
     * @return static
     */
    public function andWhere($condition, $bind = []);
}
