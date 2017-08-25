<?php

namespace ManaPHP\Db;

use ManaPHP\Db\Model\Exception as ModelException;
use ManaPHP\Di;

/**
 * Class ManaPHP\Db\Model
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
 */
class Model extends \ManaPHP\Model implements ModelInterface
{
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
     * @throws \ManaPHP\Db\Model\Exception
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
     * @return string
     */
    public static function getAutoIncrementField()
    {
        return Di::getDefault()->modelsMetadata->getAutoIncrementAttribute(get_called_class());
    }

    /**
     * @param string|array $fields
     *
     * @return \ManaPHP\Db\Model\CriteriaInterface
     */
    public static function createCriteria($fields = null)
    {
        return Di::getDefault()->get('ManaPHP\Db\Model\Criteria', [get_called_class(), $fields]);
    }

    /**
     * alias of createQuery
     *
     * @param string $alias
     *
     * @return \ManaPHP\Db\Model\QueryInterface
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
     * @return \ManaPHP\Db\Model\QueryInterface
     */
    public static function createQuery($alias = null)
    {
        return Di::getDefault()->get('ManaPHP\Db\Model\Query')->from(get_called_class(), $alias);
    }

    /**
     * Generate a SQL SELECT statement for an aggregate
     *
     * @param string $function
     * @param string $alias
     * @param string $field
     * @param array  $filters
     *
     * @return mixed
     * @throws \ManaPHP\Db\Query\Exception
     * @throws \ManaPHP\Db\Model\Exception
     */
    protected static function _groupResult($function, $alias, $field, $filters)
    {
        $criteria = static::createCriteria();

        if ($filters === null) {
            $filters = [];
        }

        if (preg_match('#^[a-z_][a-z0-9_]*$#i', $field) === 1) {
            $field = '[' . $field . ']';
        }

        $rs = $criteria->aggregate([$alias => "$function($field)"])->where($filters)->execute();

        return $rs[0][$alias];
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
     * @param array  $filters
     * @param string $field
     *
     * @return int
     * @throws \ManaPHP\Db\Query\Exception
     * @throws \ManaPHP\Db\Model\Exception
     */
    public static function count($filters = null, $field = null)
    {
        $result = self::_groupResult('COUNT', 'row_count', $field ?: '*', $filters);
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
     * @param string $field
     * @param array  $filters
     *
     * @return int|float
     * @throws \ManaPHP\Db\Query\Exception
     * @throws \ManaPHP\Db\Model\Exception
     */
    public static function sum($field, $filters = null)
    {
        return self::_groupResult('SUM', 'summary', $field, $filters);
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
     * @param string $field
     * @param array  $filters
     *
     * @return int|float
     * @throws \ManaPHP\Db\Query\Exception
     * @throws \ManaPHP\Db\Model\Exception
     */
    public static function max($field, $filters = null)
    {
        return self::_groupResult('MAX', 'maximum', $field, $filters);
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
     * @param string $field
     * @param array  $filters
     *
     * @return int|float
     * @throws \ManaPHP\Db\Query\Exception
     * @throws \ManaPHP\Db\Model\Exception
     */
    public static function min($field, $filters = null)
    {
        return self::_groupResult('MIN', 'minimum', $field, $filters);
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
     * @param string $field
     * @param array  $filters
     *
     * @return double
     * @throws \ManaPHP\Db\Query\Exception
     * @throws \ManaPHP\Db\Model\Exception
     */
    public static function avg($field, $filters = null)
    {
        return (double)self::_groupResult('AVG', 'average', $field, $filters);
    }

    /**
     * Checks if the current record already exists or not
     *
     * @return bool
     * @throws \ManaPHP\Db\Model\Exception
     */
    protected function _exists()
    {
        $primaryKeys = static::getPrimaryKey();
        if (count($primaryKeys) === 0) {
            return false;
        }

        $conditions = [];

        foreach ($primaryKeys as $field) {
            if (!isset($this->{$field})) {
                return false;
            }
            $conditions[$field] = $this->{$field};
        }

        if (is_array($this->_snapshot)) {
            $primaryKeyEqual = true;
            foreach ($primaryKeys as $field) {
                if (!isset($this->_snapshot[$field]) || $this->_snapshot[$field] !== $this->{$field}) {
                    $primaryKeyEqual = false;
                }
            }

            if ($primaryKeyEqual) {
                return true;
            }
        }

        return static::createCriteria()->where($conditions)->exists(false);
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
     * @return void
     * @throws \ManaPHP\Db\Model\Exception
     */
    public function save()
    {
        if ($this->_exists()) {
            $this->update();
        } else {
            $this->create();
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
     * @return void
     * @throws \ManaPHP\Db\Model\Exception
     */
    public function create()
    {
        $fieldValues = [];
        foreach (static::getFields() as $field) {
            if ($this->{$field} !== null) {
                $fieldValues[$field] = $this->{$field};
            }
        }

        if (count($fieldValues) === 0) {
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

        if ($this->_fireEventCancel('beforeSave') === false || $this->_fireEventCancel('beforeCreate') === false) {
            throw new ModelException('`:model` model cannot be created because it has been cancel.'/**m092e54c70ff7ecc1a*/, ['model' => get_class($this)]);
        }

        $connection = $this->_dependencyInjector->getShared($db);
        $connection->insert($source, $fieldValues);

        $autoIncrementAttribute = static::getAutoIncrementField();
        if ($autoIncrementAttribute !== null) {
            $this->{$autoIncrementAttribute} = $connection->lastInsertId();
        }

        $this->_snapshot = $this->toArray();

        $this->_fireEvent('afterCreate');
        $this->_fireEvent('afterSave');
    }

    /**
     * @param array $fieldValues
     * @param array $filters
     *
     * @return int
     * @throws \ManaPHP\Db\Model\Exception
     */
    public static function updateAll($fieldValues, $filters)
    {
        $wheres = [];
        $bind = [];
        foreach ($filters as $field => $value) {
            preg_match('#^(\w+)\s*(.*)$#', $field, $matches);
            list(, $column, $op) = $matches;
            if ($op === '') {
                $op = '=';
            }
            $wheres[] = '[' . $column . ']' . $op . ':' . $column;
            $bind[$column] = $value;
        }

        if (($db = static::getDb($bind)) === false) {
            throw new ModelException('`:model` model db sharding for _exists failed updateAll',
                ['model' => get_called_class(), 'context' => $bind]);
        }

        if (($source = static::getSource($bind)) === false) {
            throw new ModelException('`:model` model table sharding for _exists failed updateAll',
                ['model' => get_called_class(), 'context' => $bind]);
        }

        return Di::getDefault()->getShared($db)->update($source, $fieldValues, implode(' AND ', $wheres), $bind);
    }

    /**
     * @param array $filters
     *
     * @return int
     * @throws \ManaPHP\Db\Model\Exception
     */
    public static function deleteAll($filters)
    {
        $wheres = [];
        $bind = [];
        foreach ($filters as $field => $value) {
            preg_match('#^(\w+)\s*(.*)$#', $field, $matches);
            list(, $column, $op) = $matches;
            if ($op === '') {
                $op = '=';
            }
            $wheres[] = '[' . $column . ']' . $op . ':' . $column;
            $bind[$column] = $value;
        }

        if (($db = static::getDb($bind)) === false) {
            throw new ModelException('`:model` model db sharding for deleteAll failed',
                ['model' => get_called_class(), 'context' => $bind]);
        }

        if (($source = static::getSource($bind)) === false) {
            throw new ModelException('`:model` model db sharding for deleteAll failed',
                ['model' => get_called_class(), 'context' => $bind]);
        }

        return Di::getDefault()->getShared($db)->delete($source, implode(' AND ', $wheres), $bind);
    }
}