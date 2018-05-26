<?php

namespace ManaPHP\Mongodb;

use ManaPHP\Di;
use ManaPHP\Exception\InvalidFormatException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\NotImplementedException;
use ManaPHP\Exception\PreconditionException;
use ManaPHP\Exception\RuntimeException;
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
     * @var bool
     */
    protected static $_defaultAllowNullValue = false;

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
    public function getDb($context = null)
    {
        return 'mongodb';
    }

    /**
     * @param bool $allow
     */
    public static function setDefaultAllowNullValue($allow)
    {
        self::$_defaultAllowNullValue = $allow;
    }

    /**
     * @param mixed $context
     *
     * @return \ManaPHP\MongodbInterface
     */
    public function getConnection($context = null)
    {
        $db = $this->getDb($context);
        return $this->_di->getShared($db);
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        static $cached = [];

        $calledClass = get_called_class();

        if (!isset($cached[$calledClass])) {
            $fields = $this->getFields();

            if (in_array('id', $fields, true)) {
                return $cached[$calledClass] = 'id';
            }

            $source = $this->getSource();
            $pos = strrpos($source, '_');
            $tryField = ($pos === false ? $source : substr($source, $pos + 1)) . '_id';
            if (in_array($tryField, $fields, true)) {
                return $cached[$calledClass] = $tryField;
            }

            throw new NotImplementedException(['Primary key of `:model` model can not be inferred', 'model' => $calledClass]);
        }

        return $cached[$calledClass];
    }

    /**
     * @return null|string
     */
    public function getAutoIncrementField()
    {
        $primaryKey = $this->getPrimaryKey();

        return $primaryKey !== '_id' ? $primaryKey : null;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        static $cached = [];

        $calledClass = get_called_class();

        if (!isset($cached[$calledClass])) {
            return $cached[$calledClass] = array_keys($this->getFieldTypes());
        }

        return $cached[$calledClass];
    }

    /**
     * @return array
     */
    public function getIntTypeFields()
    {
        static $cached = [];

        $calledClass = get_called_class();

        if (!isset($cached[$calledClass])) {
            $fields = [];
            foreach ($this->getFieldTypes() as $field => $type) {
                if ($type === 'integer') {
                    $fields[] = $field;
                }
            }

            return $cached[$calledClass] = $fields;
        }

        return $cached[$calledClass];
    }

    /**
     * boolean, integer, double, string, array, objectid
     *
     * @return array
     */
    public function getFieldTypes()
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
                    throw new RuntimeException(['`:property` property does not contain phpdoc', 'property' => $rp->getName()]);
                }

                if (!preg_match('#@var\s+(\S+)#', $phpdoc, $match)) {
                    throw new InvalidFormatException([
                        '`:property` property phpdoc does not contain data type definition: `:phpdoc`',
                        'property' => $rp->getName(),
                        'phpdoc' => $phpdoc
                    ]);
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
                    case 'double':
                        $type = 'double';
                        break;
                    case 'bool':
                    case 'boolean':
                        $type = 'boolean';
                        break;
                    case 'array':
                    case '[]':
                        $type = 'array';
                        break;
                    case '\MongoDB\BSON\ObjectId':
                    case 'ObjectId':
                        $type = 'objectid';
                        break;
                    default:
                        throw new InvalidValueException(['`:property` property `:type` type unsupported', 'property' => $rp->getName(), 'type' => $match[1]]);
                        break;
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
    public function isAllowNullValue()
    {
        return self::$_defaultAllowNullValue;
    }

    /**
     * @return bool
     */
    protected function _createAutoIncrementIndex()
    {
        $autoIncField = $this->getAutoIncrementField();

        $command = [
            'createIndexes' => $this->getSource(),
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

        $this->getConnection()->command($command);

        return true;
    }

    /**
     * @param int $step
     * @param     int
     *
     * @return int
     */
    public function generateAutoIncrementId($step = 1)
    {
        $command = [
            'findAndModify' => 'auto_increment_id',
            'query' => ['_id' => $this->getSource()],
            'update' => ['$inc' => ['current_id' => $step]],
            'new' => true,
            'upsert' => true
        ];

        $id = $this->getConnection()->command($command)[0]['value']['current_id'];

        if ($id === $step) {
            $this->_createAutoIncrementIndex();
        }

        return $id;
    }

    /**
     * @param string $type
     * @param mixed  $value
     *
     * @return bool|float|int|string|array|\MongoDB\BSON\ObjectID|\MongoDB\BSON\UTCDateTime
     */
    public function getNormalizedValue($type, $value)
    {
        if ($value === null) {
            return null;
        }

        if ($type === 'string') {
            return is_string($value) ? $value : (string)$value;
        } elseif ($type === 'integer') {
            return is_int($value) ? $value : (int)$value;
        } elseif ($type === 'double') {
            return is_float($value) ? $value : (double)$value;
        } elseif ($type === 'objectid') {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            return is_scalar($type) ? new ObjectID($value) : $value;
        } elseif ($type === 'boolean') {
            return is_bool($value) ? $value : (bool)$value;
        } elseif ($type === 'array') {
            return (array)$value;
        } else {
            throw new InvalidValueException(['unsupported `:type` type', 'type' => $type]);
        }
    }

    /**
     * @param array                  $fields
     * @param \ManaPHP\Mongodb\Model $model
     *
     * @return \ManaPHP\Mongodb\Model\Criteria
     */
    public static function criteria($fields = null, $model = null)
    {
        return Di::getDefault()->get('ManaPHP\Mongodb\Model\Criteria', [$model ?: get_called_class(), $fields]);
    }

    /**
     * @return static
     */
    public function create()
    {
        $fields = $this->getFields();
        foreach ($this->getAutoFilledData(self::OP_CREATE) as $field => $value) {
            /** @noinspection NotOptimalIfConditionsInspection */
            if (!in_array($field, $fields, true) || $this->$field !== null) {
                continue;
            }
            $this->$field = $value;
        }

        $this->validate($fields);

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $this->_id = new ObjectID($this->_id ?: null);
        $autoIncField = $this->getAutoIncrementField();
        if ($autoIncField !== null && $this->{$autoIncField} === null) {
            $this->{$autoIncField} = $this->generateAutoIncrementId();
        }

        $allowNull = $this->isAllowNullValue();
        foreach ($this->getFieldTypes() as $field => $type) {
            if ($field === '_id') {
                continue;
            }

            if ($this->$field !== null) {
                $this->$field = $this->getNormalizedValue($type, $this->$field);
            } else {
                $this->$field = $allowNull ? null : $this->getNormalizedValue($type, '');
            }
        }

        if ($this->_fireEventCancel('beforeSave') === false || $this->_fireEventCancel('beforeCreate') === false) {
            return $this;
        }

        $fieldValues = [];
        foreach ($fields as $field) {
            if ($this->{$field} !== null) {
                $fieldValues[$field] = $this->{$field};
            } else {
                if ($allowNull) {
                    $fieldValues[$field] = null;
                }
            }
        }

        $connection = $this->_di->getShared($this->getDb($this));
        $connection->insert($this->getSource($this), $fieldValues);

        $this->_snapshot = $this->toArray();

        $this->_fireEvent('afterCreate');
        $this->_fireEvent('afterSave');

        return $this;
    }

    /**
     * Updates a model instance. If the instance does n't exist in the persistence it will throw an exception
     *
     * @return static
     */
    public function update()
    {
        $snapshot = $this->_snapshot;
        if ($snapshot === false) {
            throw new PreconditionException(['update failed: `:model` instance is snapshot disabled', 'model' => get_class($this)]);
        }

        $primaryKey = $this->getPrimaryKey();

        if (!isset($this->{$primaryKey})) {
            throw new PreconditionException(['`:model` model cannot be updated because some primary key value is not provided', 'model' => get_class($this)]);
        }

        $changedFields = [];
        $fieldTypes = $this->getFieldTypes();
        foreach ($fieldTypes as $field => $type) {
            if ($this->$field === null) {
                if (isset($snapshot[$field])) {
                    $changedFields[] = $field;
                }
            } else {
                if (!isset($snapshot[$field])) {
                    $this->$field = $this->getNormalizedValue($type, $this->$field);
                    $changedFields[] = $field;
                } elseif ($snapshot[$field] !== $this->$field) {
                    $this->$field = $this->getNormalizedValue($type, $this->$field);
                    /** @noinspection NotOptimalIfConditionsInspection */
                    if ($snapshot[$field] !== $this->$field) {
                        $changedFields[] = $field;
                    }
                }
            }
        }

        if (!$changedFields) {
            return $this;
        }

        $this->validate($changedFields);

        $fieldValues = [];
        foreach ($fieldTypes as $field => $type) {
            if ($this->$field === null) {
                if (isset($snapshot[$field])) {
                    $fieldValues[$field] = null;
                }
            } else {
                if (!isset($snapshot[$field]) || $snapshot[$field] !== $this->$field) {
                    $fieldValues[$field] = $this->$field;
                }
            }
        }

        foreach ($this->getAutoFilledData(self::OP_UPDATE) as $field => $value) {
            if (!isset($fieldTypes[$field])) {
                continue;
            }

            $this->$field = $value;
            $fieldValues[$field] = $value;
        }

        if ($this->_fireEventCancel('beforeSave') === false || $this->_fireEventCancel('beforeUpdate') === false) {
            return $this;
        }

        static::criteria(null, $this)->where($primaryKey, $this->$primaryKey)->update($fieldValues);

        $this->_snapshot = $this->toArray();

        $this->_fireEvent('afterUpdate');
        $this->_fireEvent('afterSave');

        return $this;
    }

    /**
     * @param array $pipeline
     *
     * @return array
     */
    public static function aggregate($pipeline)
    {
        $instance = new static();

        /**
         * @var \ManaPHP\MongodbInterface $connection
         */
        $connection = $instance->getConnection();
        return $connection->aggregate($instance->getSource(), $pipeline);
    }

    /**
     * @param array[] $documents
     *
     * @return int
     */
    public static function bulkInsert($documents)
    {
        $instance = new static();

        $primaryKey = $instance->getPrimaryKey();
        $allowNull = $instance->isAllowNullValue();
        $fieldTypes = $instance->getFieldTypes();
        foreach ($documents as $i => $document) {
            if (!isset($document[$primaryKey])) {
                $document[$primaryKey] = $instance->generateAutoIncrementId();
            }
            foreach ($fieldTypes as $field => $type) {
                if (isset($document[$field])) {
                    $document[$field] = $instance->getNormalizedValue($type, $document[$field]);
                } elseif ($field !== '_id') {
                    $document[$field] = $allowNull ? null : $instance->getNormalizedValue($type, '');
                }
            }
            $documents[$i] = $document;
        }

        /**
         * @var \ManaPHP\MongodbInterface $connection
         */
        $connection = $instance->getConnection();
        return $connection->bulkInsert($instance->getSource(), $documents);
    }

    /**
     * @param array $documents
     */
    public static function bulkUpdate($documents)
    {
        $instance = new static();

        $primaryKey = $instance->getPrimaryKey();
        $allowNull = $instance->isAllowNullValue();
        $fieldTypes = $instance->getFieldTypes();
        foreach ($documents as $i => $document) {
            if (!isset($document[$primaryKey])) {
                throw new InvalidValueException(['bulkUpdate `:model` model must set primary value', 'model' => get_called_class()]);
            }
            foreach ($document as $field => $value) {
                if ($value === null) {
                    $document[$field] = $allowNull ? null : $instance->getNormalizedValue($type, '');
                } else {
                    $document[$field] = $instance->getNormalizedValue($fieldTypes[$field], $value);
                }
            }
        }

        /**
         * @var \ManaPHP\MongodbInterface $connection
         */
        $connection = $instance->getConnection();
        return $connection->bulkUpdate($instance->getSource(), $documents, $primaryKey);
    }

    /**
     * @param array[] $documents
     */
    public static function bulkUpsert($documents)
    {
        $instance = new static();

        $primaryKey = $instance->getPrimaryKey();
        $allowNull = $instance->isAllowNullValue();
        $fieldTypes = $instance->getFieldTypes();
        foreach ($documents as $i => $document) {
            if (!isset($document[$primaryKey])) {
                $document[$primaryKey] = $instance->generateAutoIncrementId();
            }
            foreach ($fieldTypes as $field => $type) {
                if (isset($document[$field])) {
                    $document[$field] = $instance->getNormalizedValue($type, $document[$field]);
                } elseif ($field !== '_id') {
                    $document[$field] = $allowNull ? null : $instance->getNormalizedValue($type, '');
                }
            }
            $documents[$i] = $document;
        }

        /**
         * @var \ManaPHP\MongodbInterface $connection
         */
        $connection = $instance->getConnection();
        return $connection->bulkUpsert($instance->getSource(), $documents, $primaryKey);
    }

    /**
     * @param array $record
     * @param bool  $skipIfExists
     *
     * @return int
     */
    public static function insert($record, $skipIfExists = false)
    {
        $instance = new static();
        return $instance->getConnection($record)->insert($instance->getSource($record), $record, $instance->getPrimaryKey(), $skipIfExists);
    }

    public function __debugInfo()
    {
        return array_merge(['_id' => is_object($this->_id) ? (string)$this->_id : $this->_id], parent::__debugInfo());
    }
}