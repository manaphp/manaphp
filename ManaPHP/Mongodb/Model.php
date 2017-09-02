<?php

namespace ManaPHP\Mongodb;

use ManaPHP\Di;
use ManaPHP\Mongodb\Model\Exception as ModelException;
use MongoDB\BSON\ObjectID;

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
 */
class Model extends \ManaPHP\Model
{
    public $_id;

    /**
     * Gets the connection used to crud data to the model
     *
     * @param mixed $context
     *
     * @return string|false
     */
    public static function getDb($context = null)
    {
        return 'mongodb';
    }

    /**
     * @param mixed $context
     *
     * @return \ManaPHP\MongodbInterface
     * @throws \ManaPHP\Mongodb\Model\Exception
     */
    public static function getConnection($context = null)
    {
        $db = static::getDb($context);
        if ($db === false) {
            throw new ModelException(' db of `:model` model is invalid.', ['model' => get_called_class()]);
        }

        return Di::getDefault()->getShared($db);
    }

    /**
     * @return array
     */
    public static function getPrimaryKey()
    {
        return ['_id'];
    }

    /**
     * @return array
     */
    public static function getFields()
    {
        return array_keys(static::getFieldTypes());
    }

    public static function getAutoIncrementField()
    {
        return null;
    }

    /**
     * ```
     * bool     => Boolean
     * int      => 32-bit integer
     * long     => 64-bit integer
     * float    => Double
     * double   => Double
     * objectid => ObjectId
     * string   => String
     * ```
     *
     *
     * @return array
     */
    public static function getFieldTypes()
    {
        return ['_id' => 'objectid'];
    }

    /**
     * @param string $type
     * @param mixed  $value
     *
     * @return bool|float|int|\MongoDB\BSON\ObjectID|\MongoDB\BSON\UTCDateTime|string
     * @throws \ManaPHP\Mongodb\Model\Exception
     */
    public static function getNormalizedValue($type, $value)
    {
        if ($type === 'string') {
            return is_string($value) ? $value : (string)$value;
        } elseif ($type === 'int' || $type === 'integer' || $type === 'long') {
            return is_int($value) ? $value : (int)$value;
        } elseif ($type === 'float' || $type === 'double') {
            return is_float($value) ? $value : (float)$value;
        } elseif ($type === 'objectid') {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            return is_object($type) ? $value : new ObjectID($value);
        } elseif ($type === 'bool') {
            return is_bool($value) ? $value : (bool)$value;
        } else {
            throw new ModelException('invalid data type');
        }
    }

    /**
     * @param string $field
     *
     * @return bool|float|int|\MongoDB\BSON\ObjectID|\MongoDB\BSON\UTCDateTime|string
     * @throws \ManaPHP\Mongodb\Model\Exception
     */
    public function getNormalizedFieldValue($field)
    {
        return static::getNormalizedValue(static::getFieldTypes()[$field], $this->{$field});
    }

    /**
     * @param string|array $fields
     *
     * @return \ManaPHP\Mongodb\Model\Criteria
     */
    public static function createCriteria($fields = null)
    {
        return Di::getDefault()->get('ManaPHP\Mongodb\Model\Criteria', [get_called_class(), $fields]);
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
     * @throws \ManaPHP\Mongodb\Model\Exception
     */
    public function create()
    {
        if ($this->_fireEventCancel('beforeSave') === false || $this->_fireEventCancel('beforeCreate') === false) {
            throw new ModelException('`:model` model cannot be created because it has been cancel.'/**m092e54c70ff7ecc1a*/, ['model' => get_class($this)]);
        }

        $columnValues = [];
        foreach (self::getFields() as $field) {
            if ($this->{$field} !== null) {
                $columnValues[$field] = $this->getNormalizedFieldValue($field);
            }
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

        $_id = $connection->insert($source, $columnValues);
        $this->_id = $_id instanceof ObjectID ? (string)$_id : $_id;
        $this->_snapshot = $this->toArray();

        $this->_fireEvent('afterCreate');
        $this->_fireEvent('afterSave');
    }
}