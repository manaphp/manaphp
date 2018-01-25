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
     * @return string
     */
    public static function getPrimaryKey()
    {
        $fieldTypes = static::getFieldTypes();
        if (isset($fieldTypes['id'])) {
            return 'id';
        }

        $source = static::getSource();
        $pos = strrpos($source, '_');
        $tryField = ($pos === false ? $source : substr($source, $pos + 1)) . '_id';
        if (isset($fieldTypes[$tryField])) {
            return $tryField;
        }

        return '_id';
    }

    /**
     * @return null|string
     */
    public static function getAutoIncrementField()
    {
        $fieldTypes = static::getFieldTypes();

        if (isset($fieldTypes['id']) && $fieldTypes['id'] === 'integer') {
            return 'id';
        }

        $source = static::getSource();
        $pos = strrpos($source, '_');
        $tryField = ($pos === false ? $source : substr($source, $pos + 1)) . '_id';
        if (isset($fieldTypes[$tryField]) && $fieldTypes[$tryField] === 'integer') {
            return $tryField;
        }

        if ($fieldTypes['_id'] === 'integer') {
            return '_id';
        }

        return null;
    }

    /**
     * @return array
     */
    public static function getFields()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return array_keys(static::getFieldTypes());
    }

    /**
     * @return array|null
     */
    public static function getIntTypeFields()
    {
        return null;
    }

    /**
     * ```
     * bool     => Boolean
     * integer  => 32-bit integer
     * float    => Double
     * objectid => ObjectId
     * string   => String
     * ```
     *
     * @return array
     */
    public static function getFieldTypes()
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        /** @noinspection PhpUnhandledExceptionInspection */
        throw new ModelException('`:model::getFieldTypes`method must implemented', ['model' => get_called_class()]);
    }

    /**
     * @param int $step
     * @param     int
     *
     * @return int
     * @throws \ManaPHP\Mongodb\Model\Exception
     */
    public static function generateAutoIncrementId($step = 1)
    {
        $command = [
            'findAndModify' => 'auto_increment_id',
            'query' => ['_id' => static::getSource()],
            'update' => ['$inc' => ['current_id' => $step]],
            'new' => true,
            'upsert' => true
        ];

        $r = static::getConnection()->command($command);
        $r->setTypeMap(['root' => 'array', 'document' => 'array']);
        $r = $r->toArray();
        return $r[0]['value']['current_id'];
    }

    /**
     * @param string $type
     * @param mixed  $value
     *
     * @return bool|float|int|string|\MongoDB\BSON\ObjectID|\MongoDB\BSON\UTCDateTime
     * @throws \ManaPHP\Mongodb\Model\Exception
     */
    public static function getNormalizedValue($type, $value)
    {
        if ($value === null) {
            return null;
        }

        if ($type === 'string') {
            return is_string($value) ? $value : (string)$value;
        } elseif ($type === 'integer') {
            return is_int($value) ? $value : (int)$value;
        } elseif ($type === 'float') {
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
     * @param string|array $fields
     *
     * @return \ManaPHP\Mongodb\Model\Criteria
     */
    public static function criteria($fields = null)
    {
        return Di::getDefault()->get('ManaPHP\Mongodb\Model\Criteria', [get_called_class(), $fields]);
    }

    /**
     * @throws \ManaPHP\Mongodb\Model\Exception
     */
    protected function _preCreate()
    {
        $autoIncField = static::getAutoIncrementField();
        if ($autoIncField !== null) {
            if ($this->{$autoIncField} === null) {
                $this->{$autoIncField} = static::generateAutoIncrementId();
            }

            if ($autoIncField !== '_id' && $this->_id === null) {
                $this->_id = $this->{$autoIncField};
            }
        }

        if ($this->_id === null) {
            $primaryKey = static::getPrimaryKey();
            if ($primaryKey !== '_id' && isset($this->{$primaryKey})) {
                $this->_id = $this->{$primaryKey};
            } else {
                $fileTypes = static::getFieldTypes();
                if ($fileTypes['_id'] === 'objectid') {
                    /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                    $this->_id = (string)new ObjectID();
                }
            }
        }

        foreach (static::getFieldTypes() as $field => $type) {
            $this->{$field} = static::getNormalizedValue($type, $this->{$field});
        }
    }
}