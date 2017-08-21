<?php

namespace ManaPHP\Mvc;

use ManaPHP\Component;
use ManaPHP\Di;
use ManaPHP\Mvc\Model\Criteria;
use ManaPHP\Mvc\Model\Exception as ModelException;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Mvc\Model
 *
 * @package model
 *
 * @method void initialize()
 * @method void onConstruct()
 *
 * method beforeCreate()
 * method afterCreate()
 *
 * method beforeSave()
 * method afterSave()
 *
 * method afterFetch()
 *
 * method beforeUpdate()
 * method afterUpdate()
 *
 * method beforeDelete()
 * method afterDelete()
 *
 * @property \ManaPHP\Mvc\Model\MetadataInterface $modelsMetadata
 * @property \ManaPHP\Mvc\Model\ManagerInterface  $modelsManager
 */
class Model extends Component implements ModelInterface, \JsonSerializable
{
    /**
     * @var array
     */
    protected $_snapshot = [];

    /**
     * \ManaPHP\Mvc\Model constructor
     *
     * @param array $data
     */
    final public function __construct($data = [])
    {
        if (count($data) !== 0) {
            $this->_snapshot = $data;
            foreach ($data as $attribute => $value) {
                $this->{$attribute} = $value;
            }

            if (method_exists($this, 'afterFetch')) {
                $this->afterFetch();
            }
        }
    }

    /**
     * Returns table name mapped in the model
     *
     * @param mixed $context
     *
     * @return string|false
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public static function getSource($context = null)
    {
        $modelName = get_called_class();
        return Text::underscore(Text::contains($modelName, '\\') ? substr($modelName, strrpos($modelName, '\\') + 1) : $modelName);
    }

    /**
     * Gets the connection used to crud data to the model
     *
     * @param mixed $context
     *
     * @return string|false
     */
    public static function getDb($context = null)
    {
        return 'db';
    }

    /**
     * @param mixed $context
     *
     * @return \ManaPHP\DbInterface|false
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public static function getConnection($context = null)
    {
        $db = static::getDb($context);
        if ($db === false) {
            return false;
        }

        return Di::getDefault()->getShared($db);
    }

    /**
     * @return array
     */
    public static function getPrimaryKey()
    {
        return Di::getDefault()->modelsMetadata->getPrimaryKeyAttributes(get_called_class());
    }

    /**
     * @return array
     */
    public static function getFields()
    {
        return Di::getDefault()->modelsMetadata->getAttributes(get_called_class());
    }

    /**
     * @param string|array $columns
     *
     * @return \ManaPHP\Mvc\Model\CriteriaInterface
     */
    public static function createCriteria($columns = null)
    {
        return Di::getDefault()->get('ManaPHP\Mvc\Model\Criteria', [get_called_class(), $columns]);
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
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function assign($data, $whiteList = null)
    {
        foreach (static::getFields() as $field) {
            if (!isset($data[$field])) {
                continue;
            }

            if ($whiteList !== null && !in_array($field, $whiteList, true)) {
                continue;
            }

            $this->{$field} = $data[$field];
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
     * @param  string|array|\ManaPHP\Mvc\Model\CriteriaInterface $parameters
     * @param  int|array                                         $cacheOptions
     *
     * @return  static[]
     * @throws \ManaPHP\Db\Query\Exception
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public static function find($parameters = null, $cacheOptions = null)
    {
        if ($parameters instanceof Criteria) {
            return $parameters->execute();
        }

        $criteria = static::createCriteria()
            ->cache($cacheOptions);

        if (is_string($parameters)) {
            $parameters = [$parameters];
        }

        if (isset($parameters['columns'])) {
            $criteria->select($parameters['columns']);
            unset($parameters['columns']);
        } else {
            $criteria->select(static::getFields());
        }

        if (isset($parameters['in'])) {
            $criteria->inWhere(static::getPrimaryKey()[0], $parameters['in']);
            unset($parameters['in']);
        }

        $criteria->buildFromArray($parameters);

        $resultset = $criteria->execute();

        $modelInstances = [];
        foreach ($resultset as $key => $result) {
            $modelInstances[$key] = new static($result);
        }

        return $modelInstances;
    }

    /**
     * alias of find
     *
     * @param    string|array $parameters
     * @param   int|array     $cacheOptions
     *
     * @return  static[]
     * @throws \ManaPHP\Db\Query\Exception
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    final public static function findAll($parameters = null, $cacheOptions = null)
    {
        return self::find($parameters, $cacheOptions);
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
     * @param string|array|\ManaPHP\Mvc\Model\CriteriaInterface $parameters
     * @param int|array                                         $cacheOptions
     *
     * @return static|false
     * @throws \ManaPHP\Db\Query\Exception
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public static function findFirst($parameters = null, $cacheOptions = null)
    {
        if ($parameters instanceof Criteria) {
            return $parameters->limit(1)->execute();
        }

        $criteria = static::createCriteria()
            ->cache($cacheOptions)
            ->limit(1);

        if (is_scalar($parameters)) {
            $primaryKeys = static::getPrimaryKey();

            if (count($primaryKeys) === 0) {
                throw new ModelException('parameter is scalar, but the primary key of `:model` model is none', ['model' => get_called_class()]);
            }

            if (count($primaryKeys) !== 1) {
                throw new ModelException('parameter is scalar, but the primary key of `:model` model has more than one column'/**m0a5878bf7ea49c559*/,
                    ['model' => get_called_class()]);
            }

            $parameters = ['[' . $primaryKeys[0] . ']' . '=:' . $primaryKeys[0], 'bind' => [$primaryKeys[0] => $parameters]];
        } elseif (is_string($parameters)) {
            $parameters = [$parameters];
        }

        if (isset($parameters['columns'])) {
            $criteria->select($parameters['columns']);
            unset($parameters['columns']);
        } else {
            $criteria->select(static::getFields());
        }

        $criteria->buildFromArray($parameters);

        $resultset = $criteria->execute();

        if (isset($resultset[0])) {
            return new static($resultset[0]);
        } else {
            /** @noinspection PhpIncompatibleReturnTypeInspection */
            return false;
        }
    }

    /**
     * @param string|array $parameters
     * @param int|array    $cacheOptions
     *
     * @return bool
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public static function exists($parameters = null, $cacheOptions = null)
    {
        if (is_scalar($parameters)) {
            $primaryKeys = static::getPrimaryKey();

            if (count($primaryKeys) === 0) {
                throw new ModelException('parameter is scalar, but the primary key of `:model` model is none', ['model' => get_called_class()]);
            }

            if (count($primaryKeys) !== 1) {
                throw new ModelException('parameter is scalar, but the primary key of `:model` model has more than one column'/**m0a5878bf7ea49c559*/,
                    ['model' => get_called_class()]);
            }

            $parameters = [$primaryKeys[0] => $parameters];
        } elseif (is_string($parameters)) {
            $parameters = [$parameters];
        }

        return static::createCriteria()
            ->buildFromArray($parameters)
            ->cache($cacheOptions)
            ->exists();
    }

    /**
     * alias of createQuery
     *
     * @param string $alias
     *
     * @return \ManaPHP\Mvc\Model\QueryInterface
     * @deprecated
     */
    public static function query($alias = null)
    {
        return static::createQuery($alias);
    }

    /**
     * Create a criteria for a specific model
     *
     * @param string $alias
     *
     * @return \ManaPHP\Mvc\Model\QueryInterface
     */
    public static function createQuery($alias = null)
    {
        return Di::getDefault()->get('ManaPHP\Mvc\Model\Query')->from(get_called_class(), $alias);
    }

    /**
     * Checks if the current record already exists or not
     *
     * @return bool
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    protected function _exists()
    {
        $primaryKeys = static::getPrimaryKey();
        if (count($primaryKeys) === 0) {
            return false;
        }

        $conditions = [];
        $bind = [];

        foreach ($primaryKeys as $attributeField) {
            if (!isset($this->{$attributeField})) {
                return false;
            }

            $bindKey = $attributeField;

            $conditions[] = $attributeField . ' =:' . $bindKey;
            $bind[$bindKey] = $this->{$attributeField};
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

        if (($db = static::getDb($this)) === false) {
            throw new ModelException('`:model` model db sharding for _exists failed',
                ['model' => get_called_class(), 'context' => $this]);
        }

        if (($source = static::getSource($this)) === false) {
            throw new ModelException('`:model` model table sharding for _exists failed',
                ['model' => get_called_class(), 'context' => $this]);
        }

        $sql = 'SELECT COUNT(*) as [row_count]' . ' FROM [' . $source . '] WHERE ' . implode(' AND ',
                $conditions);

        $num = ($this->_dependencyInjector ?: Di::getDefault())->getShared($db)->getMasterConnection()->fetchOne($sql, $bind);

        return $num['row_count'] > 0;
    }

    /**
     * Generate a SQL SELECT statement for an aggregate
     *
     * @param string       $function
     * @param string       $alias
     * @param string       $column
     * @param string|array $parameters
     * @param int|array    $cacheOptions
     *
     * @return mixed
     * @throws \ManaPHP\Db\Query\Exception
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    protected static function _groupResult($function, $alias, $column, $parameters, $cacheOptions)
    {
        $criteria = static::createCriteria()
            ->cache($cacheOptions);

        if ($parameters === null) {
            $parameters = [];
        } elseif (is_string($parameters)) {
            $parameters = [$parameters];
        }

        if (preg_match('#^[a-z_][a-z0-9_]*$#i', $column) === 1) {
            $column = '[' . $column . ']';
        }
        if (isset($parameters['group'])) {
            $criteria->aggregate([$alias => "$function($column)", $parameters['group']]);
            $group = $parameters['group'];
            unset($parameters['group']);
        } /** @noinspection DefaultValueInElseBranchInspection */ else {
            $criteria->aggregate([$alias => "$function($column)"]);
        }

        $criteria->buildFromArray($parameters);

        if (isset($group)) {
            $criteria->groupBy($group);
            $rs = $criteria->execute();
            return $rs;
        } else {
            $rs = $criteria->execute();
            return $rs[0][$alias];
        }
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
     * @param string|array $parameters
     * @param string       $column
     * @param int|array    $cacheOptions
     *
     * @return int|array
     * @throws \ManaPHP\Db\Query\Exception
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public static function count($parameters = null, $column = '*', $cacheOptions = null)
    {
        $result = self::_groupResult('COUNT', 'row_count', $column, $parameters, $cacheOptions);
        if (is_string($result)) {
            $result = (int)$result;
        }

        return $result;
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
     * @param string       $column
     * @param string|array $parameters
     * @param int|array    $cacheOptions
     *
     * @return mixed
     * @throws \ManaPHP\Db\Query\Exception
     * @throws \ManaPHP\Mvc\Model\Exception
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
     * @param string       $column
     * @param string|array $parameters
     * @param int|array    $cacheOptions
     *
     * @return mixed
     * @throws \ManaPHP\Db\Query\Exception
     * @throws \ManaPHP\Mvc\Model\Exception
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
     * @param string       $column
     * @param string|array $parameters
     * @param int|array    $cacheOptions
     *
     * @return mixed
     * @throws \ManaPHP\Db\Query\Exception
     * @throws \ManaPHP\Mvc\Model\Exception
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
     * @param string       $column
     * @param string|array $parameters
     * @param int|array    $cacheOptions
     *
     * @return double
     * @throws \ManaPHP\Db\Query\Exception
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public static function avg($column, $parameters = null, $cacheOptions = null)
    {
        return (double)self::_groupResult('AVG', 'average', $column, $parameters, $cacheOptions);
    }

    /**
     * Fires an event, implicitly calls behaviors and listeners in the events manager are notified
     *
     * @param string $eventName
     *
     * @return void
     */
    protected function _fireEvent($eventName)
    {
        if (method_exists($this, $eventName)) {
            $this->{$eventName}();
        }

        $this->fireEvent('model:' . $eventName);
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
        if (method_exists($this, $eventName) && $this->{$eventName}() === false) {
            return false;
        }

        return $this->fireEvent('model:' . $eventName) !== false;
    }

    /**
     * Sends a pre-build INSERT SQL statement to the relational database system
     *
     * @return void
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    protected function _doLowInsert()
    {
        $columnValues = [];
        foreach (self::getFields() as $field) {
            if ($this->{$field} !== null) {
                $columnValues[$field] = $this->{$field};
            }
        }

        if (count($columnValues) === 0) {
            throw new ModelException('`:model` model is unable to insert without data'/**m020f0d8415e5f94d7*/, ['model' => get_class($this)]);
        }

        if (($db = static::getDb($this)) === false) {
            throw new ModelException('`:model` model db sharding for insert failed',
                ['model' => get_called_class(), 'context' => $this]);
        }

        if (($source = static::getSource($this)) === false) {
            throw new ModelException('`:model` model table sharding for insert failed',
                ['model' => get_called_class(), 'context' => $this]);
        }

        $connection = $this->_dependencyInjector->getShared($db);
        $connection->insert($source, $columnValues);

        $autoIncrementAttribute = $this->modelsMetadata->getAutoIncrementAttribute($this);
        if ($autoIncrementAttribute !== null) {
            $this->{$autoIncrementAttribute} = $connection->lastInsertId();
        }

        $this->_snapshot = $this->toArray();
    }

    /**
     * Sends a pre-build UPDATE SQL statement to the relational database system
     *
     * @return void
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    protected function _doLowUpdate()
    {
        $conditions = [];
        foreach (static::getPrimaryKey() as $attributeField) {
            if (!isset($this->{$attributeField})) {
                throw new ModelException('`:model` model cannot be updated because some primary key value is not provided'/**m0efc1ffa8444dca8d*/, ['model' => get_class($this)]);
            }

            $conditions[$attributeField] = $this->{$attributeField};
        }

        $columnValues = [];
        foreach ($this->modelsMetadata->getNonPrimaryKeyAttributes($this) as $attributeField) {
            if (isset($this->{$attributeField})) {
                /** @noinspection NestedPositiveIfStatementsInspection */
                if (!isset($this->_snapshot[$attributeField]) || $this->{$attributeField} !== $this->_snapshot[$attributeField]) {
                    $columnValues[$attributeField] = $this->{$attributeField};
                }
            }
        }

        if (count($columnValues) === 0) {
            return;
        }

        if (($db = static::getDb($this)) === false) {
            throw new ModelException('`:model` model db sharding for update failed',
                ['model' => get_called_class(), 'context' => $this]);
        }

        if (($source = static::getSource($this)) === false) {
            throw new ModelException('`:model` model table sharding for update failed',
                ['model' => get_called_class(), 'context' => $this]);
        }

        $this->_dependencyInjector->getShared($db)->update($source, $columnValues, $conditions);

        $this->_snapshot = $this->toArray();
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
     * @return void
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function save($data = null, $whiteList = null)
    {
        if ($this->_exists()) {
            $this->update($data, $whiteList);
        } else {
            $this->create($data, $whiteList);
        }
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
     * @return void
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function create($data = null, $whiteList = null)
    {
        if (is_array($data)) {
            $this->assign($data, $whiteList);
        }

        if ($this->_fireEventCancel('beforeSave') === false || $this->_fireEventCancel('beforeCreate') === false) {
            throw new ModelException('`:model` model cannot be created because it has been cancel.'/**m092e54c70ff7ecc1a*/, ['model' => get_class($this)]);
        }

        $this->_doLowInsert();
        $this->_fireEvent('afterCreate');
        $this->_fireEvent('afterSave');
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
     * @return void
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function update($data = null, $whiteList = null)
    {
        if (is_array($data)) {
            $this->assign($data, $whiteList);
        }

        if ($this->_fireEventCancel('beforeSave') === false || $this->_fireEventCancel('beforeUpdate') === false) {
            throw new ModelException('`:model` model cannot be updated because it has been cancel.'/**m0634e5c85bbe0b638*/, ['model' => get_class($this)]);
        }

        $this->_doLowUpdate();

        $this->_fireEvent('afterUpdate');
        $this->_fireEvent('afterSave');
    }

    /**
     * @param array        $columnValues
     * @param string|array $conditions
     * @param array        $bind
     *
     * @return int
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public static function updateAll($columnValues, $conditions, $bind = [])
    {
        if (($db = static::getDb($bind)) === false) {
            throw new ModelException('`:model` model db sharding for _exists failed updateAll',
                ['model' => get_called_class(), 'context' => $bind]);
        }

        if (($source = static::getSource($bind)) === false) {
            throw new ModelException('`:model` model table sharding for _exists failed updateAll',
                ['model' => get_called_class(), 'context' => $bind]);
        }

        return Di::getDefault()->getShared($db)->update($source, $columnValues, $conditions, $bind);
    }

    /**
     * @param string|array $conditions
     * @param array        $bind
     *
     * @return int
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public static function deleteAll($conditions, $bind = [])
    {
        if (($db = static::getDb($bind)) === false) {
            throw new ModelException('`:model` model db sharding for deleteAll failed',
                ['model' => get_called_class(), 'context' => $bind]);
        }

        if (($source = static::getSource($bind)) === false) {
            throw new ModelException('`:model` model db sharding for deleteAll failed',
                ['model' => get_called_class(), 'context' => $bind]);
        }

        return Di::getDefault()->getShared($db)->delete($source, $conditions, $bind);
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
     * @return void
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function delete()
    {
        $primaryKeys = static::getPrimaryKey();

        if (count($primaryKeys) === 0) {
            throw new ModelException('`:model` model must define a primary key in order to perform delete operation'/**m0d826d10544f3a078*/, ['model' => get_class($this)]);
        }

        if ($this->_fireEventCancel('beforeDelete') === false) {
            throw new ModelException('`:model` model cannot be deleted because it has been cancel.'/**m0d51bc276770c0f85*/, ['model' => get_class($this)]);
        }

        $conditions = [];
        foreach ($primaryKeys as $attributeField) {
            if (!isset($this->{$attributeField})) {
                throw new ModelException('`:model` model cannot be deleted because the primary key attribute: `:column` was not set'/**m01dec9cd3b69742a5*/,
                    ['model' => get_class($this), 'column' => $attributeField]);
            }

            $conditions[$attributeField] = $this->{$attributeField};
        }

        if (($db = static::getDb($this)) === false) {
            throw new ModelException('`:model` model db sharding for delete failed',
                ['model' => get_called_class(), 'context' => $this]);
        }

        if (($source = static::getSource($this)) === false) {
            throw new ModelException('`:model` model table sharding for delete failed',
                ['model' => get_called_class(), 'context' => $this]);
        }

        $this->_dependencyInjector->getShared($db)->delete($source, $conditions);

        $this->_fireEvent('afterDelete');
    }

    /**
     * Returns the instance as an array representation
     *
     *<code>
     * print_r($robot->toArray());
     *</code>
     *
     * @return array
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function toArray()
    {
        $data = [];

        foreach (self::getFields() as $attributeField) {
            $data[$attributeField] = isset($this->{$attributeField}) ? $this->{$attributeField} : null;
        }

        return $data;
    }

    /**
     * Returns the internal snapshot data
     *
     * @return array
     */
    public function getSnapshotData()
    {
        return $this->_snapshot;
    }

    /**
     * Returns a list of changed values
     *
     * @return array
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function getChangedFields()
    {
        $changed = [];

        foreach (self::getFields() as $field) {
            if (!isset($this->_snapshot[$field]) || $this->{$field} !== $this->_snapshot[$field]) {
                $changed[] = $field;
            }
        }

        return $changed;
    }

    /**
     * Check if a specific attribute has changed
     * This only works if the model is keeping data snapshots
     *
     * @param string|array $fields
     *
     * @return bool
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function hasChanged($fields)
    {
        if (is_string($fields)) {
            $fields = [$fields];
        }

        /** @noinspection ForeachSourceInspection */
        foreach ($fields as $field) {
            if (!isset($this->_snapshot[$field]) || $this->{$field} !== $this->_snapshot[$field]) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}