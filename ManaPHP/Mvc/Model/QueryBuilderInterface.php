<?php

namespace ManaPHP\Mvc\Model {

    /**
     * ManaPHP\Mvc\Model\Query\BuilderInterface initializer
     */
    interface QueryBuilderInterface
    {

        /**
         * Sets the columns to be queried
         *
         * @param bool $distinct
         *
         * @return static
         */

        public function distinct($distinct);

        /**
         * Sets the columns to be queried
         *
         * @param string|array $columns
         *
         * @return static
         */
        public function columns($columns);

        /**
         * Sets the models who makes part of the query
         *
         * @param string|array $models
         *
         * @return static
         */
        public function from($models);

        /**
         * Add a model to take part of the query
         *
         * @param string $model
         * @param string $alias
         *
         * @return static
         */
        public function addFrom($model, $alias = null);

        /**
         * Adds a INNER join to the query
         *
         * @param string $model
         * @param string $conditions
         * @param string $alias
         * @param string $type
         *
         * @return static
         */
        public function join($model, $conditions = null, $alias = null, $type = null);

        /**
         * Adds a INNER join to the query
         *
         * @param string $model
         * @param string $conditions
         * @param string $alias
         *
         * @return static
         */
        public function innerJoin($model, $conditions = null, $alias = null);

        /**
         * Adds a LEFT join to the query
         *
         * @param string $model
         * @param string $conditions
         * @param string $alias
         *
         * @return static
         */
        public function leftJoin($model, $conditions = null, $alias = null);

        /**
         * Adds a RIGHT join to the query
         *
         * @param string $model
         * @param string $conditions
         * @param string $alias
         *
         * @return static
         */
        public function rightJoin($model, $conditions = null, $alias = null);

        /**
         * Sets conditions for the query
         *
         * @param string $conditions
         * @param array  $bind
         *
         * @return static
         */
        public function where($conditions, $bind = null);

        /**
         * Appends a condition to the current conditions using a AND operator
         *
         * @param string $conditions
         * @param array  $bind
         *
         * @return static
         */
        public function andWhere($conditions, $bind = null);

        /**
         * Appends a BETWEEN condition to the current conditions
         *
         * @param string $expr
         * @param mixed  $min
         * @param mixed  $max
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
         * @param string $expr
         * @param mixed  $min
         * @param mixed  $max
         *
         * @return static
         */
        public function notBetweenWhere($expr, $min, $max);

        /**
         * Appends an IN condition to the current conditions
         *
         * @param string $expr
         * @param array  $values
         *
         * @return static
         */
        public function inWhere($expr, $values);

        /**
         * Appends a NOT IN condition to the current conditions
         *
         * @param string $expr
         * @param array  $values
         *
         * @return static
         */
        public function notInWhere($expr, $values);

        /**
         * Sets a ORDER BY condition clause
         *
         * @param string $orderBy
         *
         * @return static
         */
        public function orderBy($orderBy);

        /**
         * Sets a HAVING condition clause
         *
         * @param string $having
         * @param array  $bind
         *
         * @return static
         */
        public function having($having, $bind = null);

        /**
         * Sets a LIMIT clause
         *
         * @param int $limit
         * @param int $offset
         *
         * @return static
         */
        public function limit($limit, $offset = null);

        /**
         * @param int $size
         * @param int $current
         *
         * @return static
         */
        public function page($size, $current = null);

        /**
         * Sets a LIMIT clause
         *
         * @param string $group
         *
         * @return static
         */
        public function groupBy($group);

        /**
         * Returns a SQL statement built based on the builder parameters
         *
         * @return string
         */
        public function getSql();

        /**
         * Set default bind parameters
         *
         * @param array   $bind
         * @param boolean $merge
         *
         * @return static
         */
        public function setBind($bind, $merge = true);

        /**
         * @param array $options
         *
         * @return static
         */
        public function setCacheOptions($options);

        /**build the query and execute it.
         *
         * @param array $cache
         *
         * @return array
         */
        public function execute($cache = null);

        /**build the query and execute it.
         *
         * @param int   $totalRows
         * @param array $cache
         *
         * @return array
         */
        public function executeEx(&$totalRows, $cache = null);
    }
}
