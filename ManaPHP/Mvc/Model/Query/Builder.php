<?php

namespace ManaPHP\Mvc\Model\Query {

    use ManaPHP\Component;
    use ManaPHP\Db\ConditionParser;
    use ManaPHP\Di;
    use ManaPHP\Mvc\Model\Exception;
    use ManaPHP\Mvc\Model\Query;

    /**
     * ManaPHP\Mvc\Model\Query\Builder
     *
     * Helps to create SQL queries using an OO interface
     *
     *<code>
     *$resultset = $this->modelsManager->createBuilder()
     *   ->from('Robots')
     *   ->join('RobotsParts')
     *   ->limit(20)
     *   ->orderBy('Robots.name')
     *   ->getQuery()
     *   ->execute();
     *</code>
     */
    class Builder extends Component implements BuilderInterface
    {
        protected $_columns;

        /**
         * @var array
         */
        protected $_models = [];

        /**
         * @var array
         */
        protected $_joins = [];

        /**
         * @var array
         */
        protected $_conditions = [];

        protected $_group;

        /**
         * @var array
         */
        protected $_having;

        protected $_order;

        /**
         * @var int
         */
        protected $_limit;

        /**
         * @var int
         */
        protected $_offset;

        protected $_forUpdate;

        protected $_sharedLock;

        /**
         * @var array
         */
        protected $_binds = [];

        /**
         * @var bool
         */
        protected $_distinct;

        protected $_hiddenParamNumber;

        protected $_lastSQL;

        /**
         * @var boolean
         */
        protected $_uniqueRow;

        /**
         * \ManaPHP\Mvc\Model\Query\Builder constructor
         *
         *<code>
         * $params = array(
         *    'models'     => array('Users'),
         *    'columns'    => array('id', 'name', 'status'),
         *    'conditions' => array(
         *        array(
         *            "created > :min: AND created < :max:",
         *            array("min" => '2013-01-01',   'max' => '2015-01-01'),
         *            array("min" => PDO::PARAM_STR, 'max' => PDO::PARAM_STR),
         *        ),
         *    ),
         *    // or 'conditions' => "created > '2013-01-01' AND created < '2015-01-01'",
         *    'group'      => array('id', 'name'),
         *    'having'     => "name = 'lily'",
         *    'order'      => array('name', 'id'),
         *    'limit'      => 20,
         *    'offset'     => 20,
         *    // or 'limit' => array(20, 20),
         *);
         *$queryBuilder = new \ManaPHP\Mvc\Model\Query\Builder($params);
         *</code>
         *
         * @param array|string $params
         * @param \ManaPHP\Di  $dependencyInjector
         *
         * @throws \ManaPHP\Mvc\Model\Exception
         */
        public function __construct($params = null, $dependencyInjector = null)
        {
            if ($dependencyInjector !== null) {
                $this->_dependencyInjector = $dependencyInjector;
            }

            if (is_string($params)) {
                $params = [$params];
            }

            if (isset($params[0])) {
                $this->_conditions = $params[0];
            } elseif (isset($params['conditions'])) {
                $this->_conditions = $params['conditions'];
            } else {
                $this->_conditions = $params;
                $params = [];
            }

            if (isset($params['bind'])) {
                $this->_binds = array_merge($this->_binds, $params['bind']);
            }

            if (isset($params['distinct'])) {
                $this->_distinct = $params['distinct'];
            }

            if (isset($params['models'])) {
                $this->_models = $params['models'];
            }

            if (isset($params['columns'])) {
                $this->_columns = $params['columns'];
            }

            if (isset($params['joins'])) {
                $this->_joins = $params['joins'];
            }

            if (isset($params['group'])) {
                $this->_group = $params['group'];
            }

            if (isset($params['having'])) {
                $this->_having = $params['having'];
            }

            if (isset($params['order'])) {
                $this->_order = $params['order'];
            }

            if (isset($params['limit'])) {
                if (is_array($params['limit'])) {
                    throw new Exception('limit not support array format: ' . $params['limit']);
                } else {
                    $this->_limit = $params['limit'];
                }
            }

            if (isset($params['offset'])) {
                $this->_offset = $params['offset'];
            }

            if (isset($params['for_update'])) {
                $this->_forUpdate = $params['for_update'];
            }

            if (isset($params['shared_lock'])) {
                $this->_sharedLock = $params['shared_lock'];
            }
        }

        /**
         * Sets SELECT DISTINCT / SELECT ALL flag
         *
         * @param bool $distinct
         *
         * @return static
         */
        public function distinct($distinct)
        {
            $this->_distinct = $distinct;

            return $this;
        }

        /**
         * Sets the columns to be queried
         *
         *<code>
         *    $builder->columns(array('id', 'name'));
         *</code>
         *
         * @param string|array $columns
         *
         * @return static
         */
        public function columns($columns)
        {
            $this->_columns = $columns;

            return $this;
        }

        /**
         * Sets the models who makes part of the query
         *
         *<code>
         *    $builder->from('Robots');
         *    $builder->from(array('Robots', 'RobotsParts'));
         *</code>
         *
         * @param string|array $models
         *
         * @return static
         */
        public function from($models)
        {
            $this->_models = [$models];

            return $this;
        }

        /**
         * Add a model to take part of the query
         *
         *<code>
         *    $builder->addFrom('Robots', 'r');
         *</code>
         *
         * @param string $model
         * @param string $alias
         *
         * @return static
         */
        public function addFrom($model, $alias = null)
        {
            if (is_string($alias)) {
                $this->_models[$alias] = $model;
            } else {
                $this->_models[] = $model;
            }

            return $this;
        }

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
         * @param string $model
         * @param string $conditions
         * @param string $alias
         * @param string $type
         *
         * @return static
         */
        public function join($model, $conditions = null, $alias = null, $type = null)
        {
            $this->_joins[] = [$model, $conditions, $alias, $type];

            return $this;
        }

        /**
         * Adds a INNER join to the query
         *
         *<code>
         *    $builder->innerJoin('Robots');
         *    $builder->innerJoin('Robots', 'r.id = RobotsParts.robots_id');
         *    $builder->innerJoin('Robots', 'r.id = RobotsParts.robots_id', 'r');
         *</code>
         *
         * @param string $model
         * @param string $conditions
         * @param string $alias
         *
         * @return static
         */
        public function innerJoin($model, $conditions = null, $alias = null)
        {
            $this->_joins[] = [$model, $conditions, $alias, 'INNER'];

            return $this;
        }

        /**
         * Adds a LEFT join to the query
         *
         *<code>
         *    $builder->leftJoin('Robots', 'r.id = RobotsParts.robots_id', 'r');
         *</code>
         *
         * @param string $model
         * @param string $conditions
         * @param string $alias
         *
         * @return static
         */
        public function leftJoin($model, $conditions = null, $alias = null)
        {
            $this->_joins[] = [$model, $conditions, $alias, 'LEFT'];

            return $this;
        }

        /**
         * Adds a RIGHT join to the query
         *
         *<code>
         *    $builder->rightJoin('Robots', 'r.id = RobotsParts.robots_id', 'r');
         *</code>
         *
         * @param string $model
         * @param string $conditions
         * @param string $alias
         *
         * @return static
         */
        public function rightJoin($model, $conditions = null, $alias = null)
        {
            $this->_joins[] = [$model, $conditions, $alias, 'RIGHT'];

            return $this;
        }

        /**
         * Sets the query conditions
         *
         *<code>
         *    $builder->where('name = "Peter"');
         *    $builder->where('name = :name: AND id > :id:', array('name' => 'Peter', 'id' => 100));
         *</code>
         *
         * @param string $conditions
         * @param array  $binds
         *
         * @return static
         */
        public function where($conditions, $binds = null)
        {
            $this->_conditions = [$conditions];

            if ($binds !== null) {
                $this->_binds = array_merge($this->_binds, $binds);
            }

            return $this;
        }

        /**
         * Appends a condition to the current conditions using a AND operator
         *
         *<code>
         *    $builder->andWhere('name = "Peter"');
         *    $builder->andWhere('name = :name: AND id > :id:', array('name' => 'Peter', 'id' => 100));
         *</code>
         *
         * @param string $conditions
         * @param array  $binds
         *
         * @return static
         */
        public function andWhere($conditions, $binds = null)
        {
            $this->_conditions[] = $conditions;

            if ($binds !== null) {
                $this->_binds = array_merge($this->_binds, $binds);
            }

            return $this;
        }

        /**
         * Appends a BETWEEN condition to the current conditions
         *
         *<code>
         *    $builder->betweenWhere('price', 100.25, 200.50);
         *</code>
         *
         * @param string $expr
         * @param mixed  $min
         * @param mixed  $max
         *
         * @return static
         */
        public function betweenWhere($expr, $min, $max)
        {
            $minKey = 'ABP' . $this->_hiddenParamNumber++;
            $maxKey = 'ABP' . $this->_hiddenParamNumber++;

            $this->andWhere("$expr BETWEEN :$minKey AND :$maxKey", [$minKey => $min, $maxKey => $max]);

            return $this;
        }

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
        public function notBetweenWhere($expr, $min, $max)
        {
            $minKey = 'ABP' . $this->_hiddenParamNumber++;
            $maxKey = 'ABP' . $this->_hiddenParamNumber++;

            $this->andWhere("$expr NOT BETWEEN :$minKey AND :$maxKey", [$minKey => $min, $maxKey => $max]);

            return $this;
        }

        /**
         * Appends an IN condition to the current conditions
         *
         *<code>
         *    $builder->inWhere('id', [1, 2, 3]);
         *</code>
         *
         * @param string $expr
         * @param array  $values
         *
         * @return static
         */
        public function inWhere($expr, $values)
        {
            if (count($values) === 0) {
                $this->andWhere('FALSE');

                return $this;
            }

            $binds = [];
            $bindKeys = [];

            foreach ($values as $value) {
                $key = 'ABP' . $this->_hiddenParamNumber++;
                $bindKeys[] = ":$key";
                $binds[$key] = $value;
            }

            $this->andWhere($expr . ' IN (' . implode(', ', $bindKeys) . ')', $binds);

            return $this;
        }

        /**
         * Appends a NOT IN condition to the current conditions
         *
         *<code>
         *    $builder->notInWhere('id', [1, 2, 3]);
         *</code>
         *
         * @param string $expr
         * @param array  $values
         *
         * @return static
         */
        public function notInWhere($expr, $values)
        {
            if (count($values) === 0) {
                return $this;
            }

            $binds = [];
            $bindKeys = [];

            foreach ($values as $value) {
                $key = 'ABP' . $this->_hiddenParamNumber++;
                $bindKeys[] = ':' . $key;
                $binds[$key] = $value;
            }
            $this->andWhere($expr . ' NOT IN (' . implode(', ', $bindKeys) . ')', $binds);

            return $this;
        }

        /**
         * Sets a ORDER BY condition clause
         *
         *<code>
         *    $builder->orderBy('Robots.name');
         *    $builder->orderBy(array('1', 'Robots.name'));
         *</code>
         *
         * @param string $orderBy
         *
         * @return static
         */
        public function orderBy($orderBy)
        {
            $this->_order = $orderBy;

            return $this;
        }

        /**
         * Sets a HAVING condition clause. You need to escape SQL reserved words using [ and ] delimiters
         *
         *<code>
         *    $builder->having('SUM(Robots.price) > 0');
         *</code>
         *
         * @param string $having
         * @param array  $binds
         *
         * @return static
         */
        public function having($having, $binds = null)
        {
            if ($this->_having === null) {
                $this->_having = [$having];
            } else {
                $this->_having[] = $having;
            }

            if ($binds !== null) {
                $this->_binds = array_merge($this->_binds, $binds);
            }

            return $this;
        }

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
        public function limit($limit, $offset = null)
        {
            $this->_limit = $limit;
            if (isset($offset)) {
                $this->_offset = $offset;
            }

            return $this;
        }

        /**
         * Sets an OFFSET clause
         *
         *<code>
         *    $builder->offset(30);
         *</code>
         *
         * @param int $offset
         *
         * @return static
         */
        public function offset($offset)
        {
            $this->_offset = $offset;

            return $this;
        }

        /**
         * Sets a GROUP BY clause
         *
         *<code>
         *    $builder->groupBy(array('Robots.name'));
         *</code>
         *
         * @param string $group
         *
         * @return static
         */
        public function groupBy($group)
        {
            $this->_group = $group;

            return $this;
        }

        /**
         * Returns a SQL statement built based on the builder parameters
         *
         * @return string
         * @throws \ManaPHP\Mvc\Model\Exception|\ManaPHP\Db\ConditionParser\Exception
         */
        public function getSql()
        {
            if (count($this->_models) === 0) {
                throw new Exception('At least one model is required to build the query');
            }

            /**
             * Generate SQL for SELECT
             */
            $sql = 'SELECT ';

            /**
             * Generate SQL for DISTINCT
             */
            if ($this->_distinct) {
                $sql .= 'DISTINCT ';
            }

            /**
             * Generate SQL for columns
             */
            if ($this->_columns !== null) {
                if (is_array($this->_columns)) {
                    $sql .= implode(', ', $this->_columns);
                } else {
                    $sql .= preg_replace('/(\s+)/', ' ', $this->_columns);
                }
            } else {
                if (count($this->_models) === 1) {
                    $sql .= '*';
                } else {
                    $selectedColumns = [];
                    foreach ($this->_models as $alias => $model) {
                        if (is_int($alias)) {
                            $selectedColumns[] = '[' . $model . '].*';
                        } else {
                            $selectedColumns[] = '`' . $alias . '`.*';
                        }
                    }
                    $sql .= implode(', ', $selectedColumns);
                }
            }

            /**
             *  generate for FROM
             */
            $selectedModels = [];
            foreach ($this->_models as $alias => $model) {
                if (is_string($alias)) {
                    $selectedModels[] = '[' . $model . '] AS `' . $alias . '`';
                } else {
                    $selectedModels[] = '[' . $model . ']';
                }
            }
            $sql .= ' FROM ' . implode(', ', $selectedModels);

            /**
             *  Join multiple models
             */

            foreach ($this->_joins as $join) {
                list($joinModel, $joinCondition, $joinAlias, $joinType) = $join;
                if ($joinAlias !== null) {
                    $this->_models[$joinAlias] = $joinModel;
                } else {
                    $this->_models[] = $joinModel;
                }

                if ($joinType !== null) {
                    $sql .= ' ' . $joinType;
                }

                $sql .= ' JOIN [' . $joinModel . ']';

                if ($joinAlias !== null) {
                    $sql .= ' AS `' . $joinAlias . '`';
                }

                if ($joinCondition) {
                    $sql .= ' ON ' . $joinCondition;
                }
            }

            $conditions = (new ConditionParser())->parse($this->_conditions, $conditionBinds);
            if ($conditions !== '') {
                $sql .= ' WHERE ' . $conditions;
            }

            $this->_binds = array_merge($this->_binds, $conditionBinds);

            /**
             * Process group parameters
             */
            if ($this->_group !== null) {
                $sql .= ' GROUP BY ' . $this->_group;
            }

            /**
             * Process having parameters
             */
            if ($this->_having !== null) {
                if (count($this->_having) === 1) {
                    $sql .= ' HAVING ' . $this->_having[0];
                } else {
                    $sql .= ' HAVING (' . implode(' AND ', $this->_having) . ')';
                }
            }

            /**
             * Process order clause
             */
            if ($this->_order !== null) {
                if (is_array($this->_order)) {
                    $sql .= ' ORDER BY ' . implode(', ', $this->_order);
                } else {
                    $sql .= ' ORDER BY ' . $this->_order;
                }
            }

            /**
             * Process limit parameters
             */
            if ($this->_limit !== null) {
                $limit = $this->_limit;
                if (is_int($limit) || (is_string($limit) && ((string)((int)$limit))) === $limit) {
                    $sql .= ' LIMIT ' . $limit;
                } else {
                    throw new Exception('limit is invalid: ' . $limit);
                }
            }

            if ($this->_offset !== null) {
                $offset = $this->_offset;
                if (is_int($offset) || (is_string($offset) && ((string)((int)$offset))) === $offset) {
                    $sql .= ' OFFSET ' . $offset;
                } else {
                    throw new Exception('offset is invalid: ' . $offset);
                }
            }

            return $sql;
        }

        /**
         * Returns the query built
         *
         * @return \ManaPHP\Mvc\Model\QueryInterface
         * @throws \ManaPHP\Mvc\Model\Exception|\ManaPHP\Db\ConditionParser\Exception|\ManaPHP\Di\Exception
         */
        public function getQuery()
        {
            $this->_lastSQL = $this->getSql();

            $query = $this->_dependencyInjector->get('ManaPHP\Mvc\Model\Query',
                [$this->_lastSQL, $this->_models, $this->_dependencyInjector]);
            $query->setBinds($this->_binds);

            return $query;
        }

        /**
         * @param array $binds
         * @param array $cache
         *
         * @return array
         * @throws \ManaPHP\Mvc\Model\Exception|\ManaPHP\Db\ConditionParser\Exception|\ManaPHP\Di\Exception
         */
        public function execute($binds = null, $cache = null)
        {
            return $this->getQuery()->cache($cache)->execute($binds);
        }
    }
}
