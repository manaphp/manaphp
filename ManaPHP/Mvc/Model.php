<?php

namespace ManaPHP\Mvc {

    use ManaPHP\Component;
    use ManaPHP\Db\ConditionParser;
    use ManaPHP\Di;
    use ManaPHP\Mvc\Model\Exception;

    /**
     * ManaPHP\Mvc\Model
     *
     * <p>ManaPHP\Mvc\Model connects business objects and database tables to create
     * a persistent domain model where logic and data are presented in one wrapping.
     * It's an implementation of the object-relational mapping (ORM).</p>
     *
     * <p>A model represents the information (data) of the application and the rules to manipulate that data.
     * Models are primarily used for managing the rules of interaction with a corresponding database table.
     * In most cases, each table in your database will correspond to one model in your application.
     * The bulk of your application’s business logic will be concentrated in the models.</p>
     *
     * <code>
     *
     * $robot = new Robots();
     * $robot->type = 'mechanical';
     * $robot->name = 'Boy';
     * $robot->year = 1952;
     * if ($robot->save() == false) {
     *  echo "Umh, We can store robots: ";
     *  foreach ($robot->getMessages() as $message) {
     *    echo $message;
     *  }
     * } else {
     *  echo "Great, a new robot was saved successfully!";
     * }
     * </code>
     *
     * @method initialize()
     */
    class Model extends Component implements ModelInterface
    {
        /**
         * @var \ManaPHP\Mvc\Model\ManagerInterface
         */
        protected $_modelsManager;

        protected $_modelsMetaData;

        /**
         * @var array
         */
        protected $_snapshot;

        /**
         * \ManaPHP\Mvc\Model constructor
         *
         * @param array                $data
         * @param \ManaPHP\DiInterface $dependencyInjector
         *
         * @throws \ManaPHP\Di\Exception
         */
        final public function __construct($data = null, $dependencyInjector = null)
        {
            $this->_dependencyInjector = $dependencyInjector ?: Di::getDefault();
            $this->_modelsManager = $this->_dependencyInjector->getShared('modelsManager');

            /**
             * The manager always initializes the object
             */
            $this->_modelsManager->initModel($this);

            /**
             * This allows the developer to execute initialization stuff every time an instance is created
             */
            if (method_exists($this, 'onConstruct')) {
                $this->onConstruct();
            }

            if ($data !== null) {
                $this->_snapshot = $data;
                foreach ($data as $attribute => $value) {
                    $this->{$attribute} = $value;
                }
            }
        }

        /**
         * Returns the models meta-data service related to the entity instance
         *
         * @return \ManaPHP\Mvc\Model\MetaDataInterface
         * @throws \ManaPHP\Di\Exception
         */
        protected function _getModelsMetaData()
        {
            if (!is_object($this->_modelsMetaData)) {
                $this->_modelsMetaData = $this->_dependencyInjector->getShared('modelsMetadata');
            }

            return $this->_modelsMetaData;
        }

        /**
         * Sets table name which model should be mapped
         *
         * @param $source
         *
         * @return static
         */
        protected function setSource($source)
        {
            $this->_modelsManager->setModelSource($this, $source);

            return $this;
        }

        /**
         * Returns table name mapped in the model
         *
         * @return string
         */
        public function getSource()
        {
            return $this->_modelsManager->getModelSource($this);
        }

        /**
         * Sets the DependencyInjection connection service name
         *
         * @param string $connectionService
         *
         * @return static
         */
        public function setConnectionService($connectionService)
        {
            $this->_modelsManager->setConnectionService($this, $connectionService);

            return $this;
        }

        /**
         * Sets the DependencyInjection connection service name used to read data
         *
         * @param string $connectionService
         *
         * @return static
         */
        public function setReadConnectionService($connectionService)
        {
            $this->_modelsManager->setReadConnectionService($this, $connectionService);

            return $this;
        }

        /**
         * Sets the DependencyInjection connection service name used to write data
         *
         * @param string $connectionService
         *
         * @return static
         */
        public function setWriteConnectionService($connectionService)
        {
            $this->_modelsManager->setWriteConnectionService($this, $connectionService);

            return $this;
        }

        /**
         * Returns the DependencyInjection connection service name used to read data related the model
         *
         * @return string
         */
        public function getReadConnectionService()
        {
            return $this->_modelsManager->getReadConnectionService($this);
        }

        /**
         * Returns the DependencyInjection connection service name used to write data related to the model
         *
         * @return string
         */
        public function getWriteConnectionService()
        {
            return $this->_modelsManager->getWriteConnectionService($this);
        }

        /**
         * Gets the connection used to read data for the model
         *
         * @return \ManaPHP\DbInterface
         */
        public function getReadConnection()
        {
            return $this->_modelsManager->getReadConnection($this);
        }

        /**
         * Gets the connection used to write data to the model
         *
         * @return \ManaPHP\DbInterface
         */
        public function getWriteConnection()
        {
            return $this->_modelsManager->getWriteConnection($this);
        }

        /**
         * Assigns values to a model from an array
         *
         *<code>
         *$robot->assign(array(
         *  'type' => 'mechanical',
         *  'name' => 'Boy',
         *  'year' => 1952
         *));
         *</code>
         *
         * @param array $data
         * @param array $whiteList
         *
         * @return static
         * @throws \ManaPHP\Mvc\Model\Exception|\ManaPHP\Di\Exception
         */
        public function assign($data, $whiteList = null)
        {
            foreach ($this->_getModelsMetaData()->getAttributes($this) as $attribute) {
                if (!isset($data[$attribute])) {
                    continue;
                }

                if ($whiteList !== null && !in_array($attribute, $whiteList, true)) {
                    continue;
                }

                $this->{$attribute} = $data[$attribute];
            }

            return $this;
        }

        /**
         * Allows to query a set of records that match the specified conditions
         *
         * <code>
         *
         * //How many robots are there?
         * $robots = Robots::find();
         * echo "There are ", count($robots), "\n";
         *
         * //How many mechanical robots are there?
         * $robots = Robots::find("type='mechanical'");
         * echo "There are ", count($robots), "\n";
         *
         * //Get and print virtual robots ordered by name
         * $robots = Robots::find(array("type='virtual'", "order" => "name"));
         * foreach ($robots as $robot) {
         *       echo $robot->name, "\n";
         * }
         *
         * //Get first 100 virtual robots ordered by name
         * $robots = Robots::find(array("type='virtual'", "order" => "name", "limit" => 100));
         * foreach ($robots as $robot) {
         *       echo $robot->name, "\n";
         * }
         * </code>
         *
         * @param    array $parameters
         * @param  array   $cacheOptions
         *
         * @return  static[]|false
         * @throws \ManaPHP\Di\Exception
         */
        public static function find($parameters = null, $cacheOptions = null)
        {
            $dependencyInjector = Di::getDefault();
            $modelsManager = $dependencyInjector->getShared('modelsManager');

            $builder = $modelsManager->createBuilder($parameters);
            $builder->from(get_called_class());

            $query = $builder->getQuery();

            if (isset($params['bind'])) {
                $query->setBinds($params['bind'], true);
            }

            $query->cache($cacheOptions);

            $resultset = $query->execute();

            if (is_array($resultset)) {
                $modelInstances = [];
                foreach ($resultset as $result) {
                    $modelInstances[] = new static($result, $dependencyInjector);
                }

                return $modelInstances;
            } else {
                return false;
            }
        }

        /**
         * Allows to query the first record that match the specified conditions
         *
         * <code>
         *
         * //What's the first robot in robots table?
         * $robot = Robots::findFirst();
         * echo "The robot name is ", $robot->name;
         *
         * //What's the first mechanical robot in robots table?
         * $robot = Robots::findFirst("type='mechanical'");
         * echo "The first mechanical robot name is ", $robot->name;
         *
         * //Get first virtual robot ordered by name
         * $robot = Robots::findFirst(array("type='virtual'", "order" => "name"));
         * echo "The first virtual robot name is ", $robot->name;
         *
         * </code>
         *
         * @param string|array $parameters
         * @param              $cacheOptions
         *
         * @return static
         * @throws \ManaPHP\Mvc\Model\Exception|\ManaPHP\Di\Exception
         */
        public static function findFirst($parameters = null, $cacheOptions = null)
        {
            $dependencyInjector = Di::getDefault();
            $modelsManager = $dependencyInjector->getShared('modelsManager');

            if (is_numeric($parameters)) {
                $modelsMetadata = $dependencyInjector->getShared('modelsMetadata');
                $primaryKeys = $modelsMetadata->getPrimaryKeyAttributes(get_called_class());

                if (count($primaryKeys) !== 1) {
                    throw new Exception('parameter is integer, but the model\'s primary key has more than one column');
                }

                $parameters = [$primaryKeys[0] => $parameters];
            } elseif (is_string($parameters)) {
                $parameters = [$parameters];
            }

            $builder = $modelsManager->createBuilder($parameters);
            $builder->from(get_called_class());
            $builder->limit(1);

            $query = $builder->getQuery();

            if (isset($parameters['bind'])) {
                $query->setBinds($parameters['bind'], true);
            }

            $query->cache($cacheOptions);
            $result = $query->execute();

            if (is_array($result) && isset($result[0])) {
                return new static($result[0], $dependencyInjector);
            } else {
                return false;
            }
        }

        /**
         * Create a criteria for a specific model
         *
         * @param \ManaPHP\DiInterface $dependencyInjector
         *
         * @return \ManaPHP\Mvc\Model\Query\BuilderInterface
         * @throws \ManaPHP\Di\Exception
         */
        public static function query($dependencyInjector = null)
        {
            $dependencyInjector = $dependencyInjector ?: Di::getDefault();

            return $dependencyInjector->getShared('modelsManager')->createBuilder();
        }

        /**
         * Checks if the current record already exists or not
         *
         * @param \ManaPHP\Mvc\Model\MetadataInterface $metaData
         * @param \ManaPHP\DbInterface                 $connection
         *
         * @return boolean
         * @throws \ManaPHP\Mvc\Model\Exception
         */
        protected function _exists($metaData, $connection)
        {
            $primaryKeys = $metaData->getPrimaryKeyAttributes($this);
            if (count($primaryKeys) === 0) {
                return false;
            }

            $conditions = [];
            $binds = [];

            foreach ($primaryKeys as $attributeField) {
                if (!isset($this->{$attributeField})) {
                    return false;
                }

                $bindKey = ':' . $attributeField;

                $conditions[] = $attributeField . ' =' . $bindKey;
                $binds[$bindKey] = $this->{$attributeField};
            }

            if (is_array($this->_snapshot)) {
                $primaryKeyEqual = true;
                foreach ($primaryKeys as $attributeField) {
                    if (!isset($this->_snapshot[$attributeField]) || $this->_snapshot[$attributeField] !== $this->{$attributeField}) {
                        $primaryKeyEqual = false;
                    }
                }

                if ($primaryKeyEqual) {
                    return true;
                }
            }

            $sql = 'SELECT COUNT(*) as row_count' . ' FROM `' . $this->getSource() . '` WHERE ' . implode(' AND ',
                    $conditions);
            $num = $connection->fetchOne($sql, $binds, \PDO::FETCH_ASSOC);

            return $num['row_count'] > 0;
        }

        /**
         * Generate a SQL SELECT statement for an aggregate
         *
         * @param string       $function
         * @param string       $alias
         * @param string       $column
         * @param string|array $parameters
         * @param array        $cacheOptions
         *
         * @return mixed
         * @throws \ManaPHP\Di\Exception
         */
        protected static function _groupResult($function, $alias, $column, $parameters, $cacheOptions)
        {
            $dependencyInjector = Di::getDefault();
            $modelsManager = $dependencyInjector->getShared('modelsManager');
            if ($parameters === null) {
                $parameters = [];
            } elseif (is_string($parameters)) {
                $parameters = [$parameters];
            }

            $columns = $function . '(' . $column . ') AS ' . $alias;

            $builder = $modelsManager->createBuilder($parameters);
            $builder->columns($columns);
            $builder->from(get_called_class());

            $query = $builder->getQuery();

            $query->cache($cacheOptions);

            if (isset($parameters['bind'])) {
                $resultset = $query->execute([$parameters['bind']]);
            } else {
                $resultset = $query->execute();
            }

            return $resultset[0][$alias];
        }

        /**
         * Allows to count how many records match the specified conditions
         *
         * <code>
         *
         * //How many robots are there?
         * $number = Robots::count();
         * echo "There are ", $number, "\n";
         *
         * //How many mechanical robots are there?
         * $number = Robots::count("type='mechanical'");
         * echo "There are ", $number, " mechanical robots\n";
         *
         * </code>
         *
         * @param array  $parameters
         * @param string $column
         * @param array  $cacheOptions
         *
         * @return int
         * @throws \ManaPHP\Di\Exception
         */
        public static function count($parameters = null, $column = '*', $cacheOptions = null)
        {
            $result = self::_groupResult('COUNT', 'row_count', $column, $parameters, $cacheOptions);
            if (is_string($result)) {
                return (int)$result;
            } else {
                return $result;
            }
        }

        /**
         * Allows to calculate a summary on a column that match the specified conditions
         *
         * <code>
         *
         * //How much are all robots?
         * $sum = Robots::sum(array('column' => 'price'));
         * echo "The total price of robots is ", $sum, "\n";
         *
         * //How much are mechanical robots?
         * $sum = Robots::sum(array("type='mechanical'", 'column' => 'price'));
         * echo "The total price of mechanical robots is  ", $sum, "\n";
         *
         * </code>
         *
         * @param string $column
         * @param array  $parameters
         * @param array  $cacheOptions
         *
         * @return mixed
         * @throws \ManaPHP\Di\Exception|\ManaPHP\Mvc\Model\Exception
         */
        public static function sum($column, $parameters = null, $cacheOptions = null)
        {
            return self::_groupResult('SUM', 'summary', $column, $parameters, $cacheOptions = null);
        }

        /**
         * Allows to get the max value of a column that match the specified conditions
         *
         * <code>
         *
         * //What is the max robot id?
         * $id = Robots::max(array('column' => 'id'));
         * echo "The max robot id is: ", $id, "\n";
         *
         * //What is the max id of mechanical robots?
         * $sum = Robots::max(array("type='mechanical'", 'column' => 'id'));
         * echo "The max robot id of mechanical robots is ", $id, "\n";
         *
         * </code>
         *
         * @param string $column
         * @param array  $parameters
         * @param array  $cacheOptions
         *
         * @return mixed
         * @throws \ManaPHP\Di\Exception
         */
        public static function max($column, $parameters = null, $cacheOptions = null)
        {
            return self::_groupResult('MAX', 'maximum', $column, $parameters, $cacheOptions);
        }

        /**
         * Allows to get the min value of a column that match the specified conditions
         *
         * <code>
         *
         * //What is the min robot id?
         * $id = Robots::min(array('column' => 'id'));
         * echo "The min robot id is: ", $id;
         *
         * //What is the min id of mechanical robots?
         * $sum = Robots::min(array("type='mechanical'", 'column' => 'id'));
         * echo "The min robot id of mechanical robots is ", $id;
         *
         * </code>
         *
         * @param string $column
         * @param array  $parameters
         * @param array  $cacheOptions
         *
         * @return mixed
         * @throws \ManaPHP\Di\Exception
         */
        public static function min($column, $parameters = null, $cacheOptions = null)
        {
            return self::_groupResult('MIN', 'minimum', $column, $parameters, $cacheOptions);
        }

        /**
         * Allows to calculate the average value on a column matching the specified conditions
         *
         * <code>
         *
         * //What's the average price of robots?
         * $average = Robots::average(array('column' => 'price'));
         * echo "The average price is ", $average, "\n";
         *
         * //What's the average price of mechanical robots?
         * $average = Robots::average(array("type='mechanical'", 'column' => 'price'));
         * echo "The average price of mechanical robots is ", $average, "\n";
         *
         * </code>
         *
         * @param string $column
         * @param array  $parameters
         * @param        $cacheOptions
         *
         * @return double
         * @throws \ManaPHP\Di\Exception
         */
        public static function average($column, $parameters = null, $cacheOptions = null)
        {
            return (double)self::_groupResult('AVG', 'average', $column, $parameters, $cacheOptions);
        }

        /**
         * Fires an event, implicitly calls behaviors and listeners in the events manager are notified
         *
         * @param string $eventName
         *
         * @return boolean
         */
        protected function _fireEvent($eventName)
        {
            if (method_exists($this, $eventName)) {
                $this->{$eventName}();
            }
        }

        /**
         * Fires an internal event that cancels the operation
         *
         * @param string $eventName
         *
         * @return bool
         */
        protected function _fireEventCancel($eventName)
        {
            if (method_exists($this, $eventName)) {
                return $this->{$eventName}();
            } else {
                return null;
            }
        }

        /**
         * Executes internal hooks before save a record
         *
         * @param boolean $exists
         *
         * @return boolean
         */
        protected function _preSave($exists)
        {
            if ($this->_fireEventCancel('beforeSave') === false) {
                return false;
            }

            if ($exists) {
                if ($this->_fireEventCancel('beforeUpdate') === false) {
                    return false;
                }
            } else {
                if ($this->_fireEventCancel('beforeCreate') === false) {
                    return false;
                }
            }

            return true;
        }

        /**
         * Executes internal events after save a record
         *
         * @param boolean $exists
         */
        protected function _postSave($exists)
        {
            if ($exists) {
                $this->_fireEvent('afterUpdate');
            } else {
                $this->_fireEvent('afterCreate');
            }

            $this->_fireEvent('afterSave');
        }

        /**
         * Sends a pre-build INSERT SQL statement to the relational database system
         *
         * @param \ManaPHP\Mvc\Model\MetadataInterface $metaData
         * @param \ManaPHP\DbInterface                 $connection
         *
         * @return boolean
         * @throws \ManaPHP\Mvc\Model\Exception
         */
        protected function _doLowInsert($metaData, $connection)
        {
            $columnValues = [];
            foreach ($metaData->getAttributes($this) as $attributeField) {
                if ($this->{$attributeField} !== null) {
                    $columnValues[$attributeField] = $this->{$attributeField};
                }
            }

            if (count($columnValues) === 0) {
                throw new Exception('Unable to insert into ' . $this->getSource() . ' without data');
            }

            $success = $connection->insert($this->getSource(), $columnValues);
            if ($success) {
                $autoIncrementAttribute = $metaData->getAutoIncrementAttribute($this);
                if ($autoIncrementAttribute !== null) {
                    $this->{$autoIncrementAttribute} = $connection->lastInsertId();
                }
            }

            return $success;
        }

        /**
         * Sends a pre-build UPDATE SQL statement to the relational database system
         *
         * @param \ManaPHP\Mvc\Model\MetadataInterface $metaData
         * @param \ManaPHP\DbInterface                 $connection
         *
         * @return boolean
         * @throws \ManaPHP\Mvc\Model\Exception|\ManaPHP\Di\Exception
         */
        protected function _doLowUpdate($metaData, $connection)
        {
            $conditions = [];
            foreach ($metaData->getPrimaryKeyAttributes($this) as $attributeField) {
                if (!isset($this->{$attributeField})) {
                    throw new Exception('Record cannot be updated because it\'s some primary key has invalid value.');
                }

                $conditions[$attributeField] = $this->{$attributeField};
            }

            $columnValues = [];
            foreach ($metaData->getAttributes($this) as $attributeField) {
                if (isset($this->{$attributeField})) {
                    /** @noinspection NestedPositiveIfStatementsInspection */
                    if (!is_array($this->_snapshot) || !isset($this->_snapshot[$attributeField]) || $this->{$attributeField} !== $this->_snapshot[$attributeField]) {
                        $columnValues[$attributeField] = $this->{$attributeField};
                    }
                }
            }

            if (count($columnValues) === 0) {
                return true;
            }

            $success = $connection->update($this->getSource(), $columnValues, $conditions);

            if ($success) {
                $this->_snapshot = $this->toArray();
            }

            return $success;
        }

        /**
         * Inserts or updates a model instance. Returning true on success or false otherwise.
         *
         *<code>
         *    //Creating a new robot
         *    $robot = new Robots();
         *    $robot->type = 'mechanical';
         *    $robot->name = 'Boy';
         *    $robot->year = 1952;
         *    $robot->save();
         *
         *    //Updating a robot name
         *    $robot = Robots::findFirst("id=100");
         *    $robot->name = "Biomass";
         *    $robot->save();
         *</code>
         *
         * @param array $data
         * @param array $whiteList
         *
         * @return boolean
         * @throws \ManaPHP\Mvc\Model\Exception|\ManaPHP\Di\Exception
         */
        public function save($data = null, $whiteList = null)
        {
            if (is_array($data) && count($data) > 0) {
                $this->assign($data, $whiteList);
            }

            $metaData = $this->_getModelsMetaData();
            $writeConnection = $this->getWriteConnection();

            $exists = $this->_exists($metaData, $writeConnection);
            if ($this->_preSave($exists) === false) {
                throw new Exception('Record cannot be saved because it has been cancel.');
            }

            if ($exists) {
                $success = $this->_doLowUpdate($metaData, $writeConnection);
            } else {
                $success = $this->_doLowInsert($metaData, $writeConnection);
            }

            if ($success) {
                $this->_fireEvent('afterSave');
            }

            return $success;
        }

        /**
         * Inserts a model instance. If the instance already exists in the persistence it will throw an exception
         * Returning true on success or false otherwise.
         *
         *<code>
         *    //Creating a new robot
         *    $robot = new Robots();
         *    $robot->type = 'mechanical';
         *    $robot->name = 'Boy';
         *    $robot->year = 1952;
         *    $robot->create();
         *
         *  //Passing an array to create
         *  $robot = new Robots();
         *  $robot->create(array(
         *      'type' => 'mechanical',
         *      'name' => 'Boy',
         *      'year' => 1952
         *  ));
         *</code>
         *
         * @param array $data
         * @param array $whiteList
         *
         * @return boolean
         * @throws \ManaPHP\Mvc\Model\Exception|\ManaPHP\Di\Exception
         */
        public function create($data = null, $whiteList = null)
        {
            if ($this->_exists($this->_getModelsMetaData(), $this->getReadConnection())) {
                throw new Exception('Record cannot be created because it already exists');
            }

            return $this->save($data, $whiteList);
        }

        /**
         * Updates a model instance. If the instance does n't exist in the persistence it will throw an exception
         * Returning true on success or false otherwise.
         *
         *<code>
         *    //Updating a robot name
         *    $robot = Robots::findFirst("id=100");
         *    $robot->name = "Biomass";
         *    $robot->update();
         *</code>
         *
         * @param array $data
         * @param array $whiteList
         *
         * @return boolean
         * @throws \ManaPHP\Mvc\Model\Exception|\ManaPHP\Di\Exception
         */
        public function update($data = null, $whiteList = null)
        {
            if (!$this->_exists($this->_getModelsMetaData(), $this->getReadConnection())) {
                throw new Exception('Record cannot be updated because it does not exist');
            }

            return $this->save($data, $whiteList);
        }

        /**
         * Deletes a model instance. Returning true on success or false otherwise.
         *
         * <code>
         *$robot = Robots::findFirst("id=100");
         *$robot->delete();
         *
         *foreach (Robots::find("type = 'mechanical'") as $robot) {
         *   $robot->delete();
         *}
         * </code>
         *
         * @return boolean
         * @throws \ManaPHP\Mvc\Model\Exception|\ManaPHP\Di\Exception
         */
        public function delete()
        {
            $metaData = $this->_getModelsMetaData();
            $writeConnection = $this->getWriteConnection();
            $primaryKeys = $metaData->getPrimaryKeyAttributes($this);

            if (count($primaryKeys) === 0) {
                throw new Exception('A primary key must be defined in the model in order to perform the operation');
            }

            if ($this->_fireEventCancel('beforeDelete') === false) {
                return false;
            }

            $conditions = [];
            foreach ($primaryKeys as $attributeField) {

                /**
                 * If the attribute is currently set in the object add it to the conditions
                 */
                if (!isset($this->{$attributeField})) {
                    throw new Exception("Cannot delete the record because the primary key attribute: '" . $attributeField . "' wasn't set");
                }

                $conditions[$attributeField] = $this->{$attributeField};
            }

            $success = $writeConnection->delete($this->getSource(), $conditions);

            if ($success === true) {
                $this->_fireEvent('afterDelete');
            }

            return $success;
        }

        /**
         * Returns the instance as an array representation
         *
         *<code>
         * print_r($robot->toArray());
         *</code>
         *
         * @return array
         * @throws \ManaPHP\Di\Exception
         */
        public function toArray()
        {
            $data = [];

            foreach ($this->_getModelsMetaData()->getAttributes($this) as $attributeField) {
                $data[$attributeField] = isset($this->{$attributeField}) ? $this->{$attributeField} : null;
            }

            return $data;
        }

        /**
         * @return array
         */
        public function __debugInfo()
        {
            $data = [];

            foreach (parent::__debugInfo() as $k => $v) {
                if ($k === '_modelsManager') {
                    continue;
                }

                $data[$k] = $v;
            }

            return $data;
        }
    }
}
