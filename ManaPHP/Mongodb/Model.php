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
            throw new ModelException(['db of `:model` model is invalid.', 'model' => get_called_class()]);
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
        static $cached = [];

        $calledClass = get_called_class();

        if (!isset($cached[$calledClass])) {
            $fieldTypes = [];
            $rc = new \ReflectionClass(get_called_class());

            foreach ($rc->getProperties(\ReflectionProperty::IS_PUBLIC) as $rp) {
                if ($rp->isStatic()) {
                    continue;
                }

                $phpdoc = $rp->getDocComment();
                if (!$phpdoc) {
                    throw new ModelException(['`:property` property does not contain phpdoc', 'property' => $rp->getName()]);
                }

                if (!preg_match('#@var ([^\s]+)#', $phpdoc, $match)) {
                    throw new ModelException(['`:property` property phpdoc does not contain data type defintion: `:phpdoc`',
                        'property' => $rp->getName(), 'phpdoc' => $phpdoc]);
                }

                switch ($match[1]) {
                    case 'string':
                        $type = 'string';
                        break;
                    case 'int':
                    case 'integer':
                        $type = 'integer';
                        break;
                    case 'float':
                        $type = 'float';
                        break;
                    case 'bool':
                    case 'boolean':
                        $type = 'bool';
                        break;
                    case '\MongoDB\BSON\ObjectId':
                    case 'ObjectId':
                        $type = 'objectid';
                        break;
                    default:
                        throw new ModelException(['`:property` property `:type` type unsupported',
                            'property' => $rp->getName(), 'type' => $match[1]]);
                }

                $fieldTypes[$rp->getName()] = $type;
            }

            return $cached[$calledClass] = $fieldTypes;
        }

        return $cached[$calledClass];
    }

    /**
     * @return bool
     */
    protected static function _createAutoIncrementIndex()
    {
        $autoIncField = static::getAutoIncrementField();

        $command = [
            'createIndexes' => static::getSource(),
            'indexes' => [
                [
                    'key' => [
                        $autoIncField => 1
                    ],
                    'unique' => true,
                    'name' => $autoIncField
                ]
            ]
        ];

        static::getConnection()->command($command);

        return true;
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
        $id = $r[0]['value']['current_id'];

        if ($id === $step) {
            static::_createAutoIncrementIndex();
        }
		
        return $id;
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
            return is_scalar($type) ? new ObjectID($value) : $value;
        } elseif ($type === 'bool') {
            return is_bool($value) ? $value : (bool)$value;
        } else {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new ModelException(['unsupported `:type` type', 'type' => $type]);
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
            if ($field === '_id') {
                continue;
            }

            $this->{$field} = static::getNormalizedValue($type, $this->{$field} !== null ? $this->{$field} : '');
        }
    }
}