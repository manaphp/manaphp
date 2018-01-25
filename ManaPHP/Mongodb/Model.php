<?php

namespace ManaPHP\Mongodb;

use ManaPHP\Di;
use ManaPHP\Mongodb\Model\Exception as ModelException;
use MongoDB\BSON\ObjectID;

/**
 * Class ManaPHP\Mongodb\Model
 *
 * @package model
 *
 */
class Model extends \ManaPHP\Model
{
    /**
     * @var \MongoDB\BSON\ObjectId
     */
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
        static $cached = [];

        $calledClass = get_called_class();

        if (!isset($cached[$calledClass])) {
            $fields = static::getFields();

            if (in_array('id', $fields, true)) {
                return $cached[$calledClass] = 'id';
            }

            $source = static::getSource();
            $pos = strrpos($source, '_');
            $tryField = ($pos === false ? $source : substr($source, $pos + 1)) . '_id';
            if (in_array($tryField, $fields, true)) {
                return $cached[$calledClass] = $tryField;
            }

            return $cached[$calledClass] = '_id';
        }

        return $cached[$calledClass];
    }

    /**
     * @return null|string
     */
    public static function getAutoIncrementField()
    {
        $primaryKey = static::getPrimaryKey();

        return $primaryKey !== '_id' ? $primaryKey : null;
    }

    /**
     * @return array
     */
    public static function getFields()
    {
        static $cached = [];

        $calledClass = get_called_class();

        if (!isset($cached[$calledClass])) {
            return $cached[$calledClass] = array_keys(static::getFieldTypes());
        }

        return $cached[$calledClass];
    }

    /**
     * @return array
     */
    public static function getIntTypeFields()
    {
        static $cached = [];

        $calledClass = get_called_class();

        if (!isset($cached[$calledClass])) {
            $fields = [];
            foreach (static::getFieldTypes() as $field => $type) {
                if ($type === 'integer') {
                    $fields[] = $field;
                }
            }

            return $cached[$calledClass] = $fields;
        }

        return $cached[$calledClass];
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
            /** @noinspection PhpUnhandledExceptionInspection */
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
        if ($this->_id === null) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $this->_id = new ObjectID();
        } elseif (!is_object($this->_id)) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $this->_id = new ObjectID($this->_id);
        }

        $autoIncField = static::getAutoIncrementField();
        if ($autoIncField !== null && $this->{$autoIncField} === null) {
            $this->{$autoIncField} = static::generateAutoIncrementId();
        }

        foreach (static::getFieldTypes() as $field => $type) {
            if ($field !== '_id' && $this->$field !== null) {
                $this->{$field} = static::getNormalizedValue($type, $this->{$field});
            }
        }
    }
}