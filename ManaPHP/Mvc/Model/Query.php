<?php

namespace ManaPHP\Mvc\Model {

    use ManaPHP\Component;
    use ManaPHP\Mvc\Application;

    /**
     * ManaPHP\Mvc\Model\Query
     *
     * This class takes a SQL intermediate representation and executes it.
     *
     *<code>
     *
     * $sql = "SELECT c.price*0.16 AS taxes, c.* FROM Cars AS c JOIN Brands AS b
     *          WHERE b.name = :name: ORDER BY c.name";
     *
     * $result = $manager->executeQuery($sql, array(
     *   'name' => 'Lamborghini'
     * ));
     *
     * foreach ($result as $row) {
     *   echo "Name: ", $row->cars->name, "\n";
     *   echo "Price: ", $row->cars->price, "\n";
     *   echo "Taxes: ", $row->taxes, "\n";
     * }
     *
     *</code>
     */
    class Query extends Component implements QueryInterface
    {
        protected $_sql;
        protected $_cache;

        protected $_cacheOptions;

        protected $_binds;
        protected $_models = [];

        /**
         * \ManaPHP\Mvc\Model\Query constructor
         *
         * @param string               $sql
         * @param array                $models
         * @param \ManaPHP\DiInterface $dependencyInjector
         */
        public function __construct($sql, $models = null, $dependencyInjector = null)
        {
            $this->_sql = $sql;

            if (is_array($models)) {
                $this->_models = $models;
            }

            if ($dependencyInjector !== null) {
                $this->setDependencyInjector($dependencyInjector);
            }
        }

        /**
         * Sets the cache parameters of the query
         *
         * @param array $options
         *
         * @return static
         */
        public function cache($options)
        {
            return $this;
        }

        /**
         * Executes a parsed SQL statement
         *
         * @param array $binds
         *
         * @return array
         * @throws \ManaPHP\Mvc\Model\Exception
         */
        public function execute($binds = null)
        {
            if ($binds !== null) {
                $mergedBinds = array_merge($this->_binds, $binds);
            } else {
                $mergedBinds = $this->_binds;
            }

            $sql = $this->_sql;

            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $modelsManager = $this->_dependencyInjector->getShared('modelsManager');

            $readConnection = null;
            foreach ($this->_models as $model) {
                $modelInstance = $modelsManager->getModelInstance($model, false);

                if ($readConnection === null) {
                    $readConnection = $modelInstance->getReadConnection();
                }

                $sql = str_replace('[' . $model . ']', '`' . $modelInstance->getSource() . '`', $sql);
            }

            //compatible with other SQL syntax
            if (is_array($mergedBinds)) {
                $replaces = [];
                foreach ($mergedBinds as $key => $value) {
                    $replaces[':' . $key . ':'] = ':' . $key;
                }

                $sql = strtr($sql, $replaces);
            }

            try {
                $result = $readConnection->fetchAll($sql, $mergedBinds);
            } catch (\Exception $e) {
                throw new Exception($e->getMessage() . ':' . $sql);
            }

            return $result;
        }

        /**
         * Set default bind parameters
         *
         * @param array   $binds
         * @param boolean $merge
         *
         * @return static
         */
        public function setBinds($binds, $merge = false)
        {
            $this->_binds = $merge ? array_merge($this->_binds, $binds) : $binds;

            return $this;
        }
    }
}
