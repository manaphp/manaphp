<?php

namespace ManaPHP\Mongodb;

use ManaPHP\Di;
use ManaPHP\Exception\InvalidFormatException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\NotImplementedException;
use ManaPHP\Exception\PreconditionException;
use ManaPHP\Exception\RuntimeException;
use ManaPHP\Model\ExpressionInterface;
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
     * @return string
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
     * @param mixed $context
     *
     * @return \ManaPHP\MongodbInterface
     */
    public static function connection($context = null)
    {
        return (new static())->getConnection($context);
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

            $tryField = lcfirst(($pos = strrpos($calledClass, '\\')) === false ? $calledClass : substr($calledClass, $pos + 1)) . '_id';
            if (in_array($tryField, $fields, true)) {
                return $cached[$calledClass] = $tryField;
            }

            $source = $this->getSource();
            $tryField = (($pos = strpos($source, '.')) ? substr($source, $pos + 1) : $source) . '_id';
            if (in_array($tryField, $fields, true)) {
                return $cached[$calledClass] = $tryField;
            }

            throw new NotImplementedException(['Primary key of `:model` model can not be inferred', 'model' => $calledClass]);
        }

        return $cached[$calledClass];
    }

    /**
     * @return array
     */
    public function getFields()
    {
        static $cached = [];

        $calledClass = get_called_class();

        if (!isset($cached[$calledClass])) {
            $fieldTypes = $this->getFieldTypes();
            if (isset($fieldTypes['_id']) && $fieldTypes['_id'] === 'objectid') {
                unset($fieldTypes['_id']);
            }
            return $cached[$calledClass] = array_keys($fieldTypes);
        }

        return $cached[$calledClass];
    }

    /**
     * @return array
     */
    public function getIntFields()
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
        $source = $this->getSource();
        if ($pos = strpos($source, '.')) {
            $db = substr($source, 0, $pos);
            $collection = substr($source, $pos + 1);
        } else {
            $db = null;
            $collection = $source;
        }

        $command = [
            'createIndexes' => $collection,
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

        $this->getConnection()->command($command, $db);

        return true;
    }

    /**
     * @param int $step
     *
     * @return int
     */
    public function generateAutoIncrementId($step = 1)
    {
        $source = $this->getSource();
        if ($pos = strpos($source, '.')) {
            $db = substr($source, 0, $pos);
            $collection = substr($source, $pos + 1);
        } else {
            $db = null;
            $collection = $source;
        }

        $command = [
            'findAndModify' => 'auto_increment_id',
            'query' => ['_id' => $collection],
            'update' => ['$inc' => ['current_id' => $step]],
            'new' => true,
            'upsert' => true
        ];

        $id = $this->getConnection()->command($command, $db)[0]['value']['current_id'];

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
    public function normalizeValue($type, $value)
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
            throw new InvalidValueException(['`:model` model is not supported `:type` type', 'model' => get_called_class(), 'type' => $type]);
        }
    }

    /**
     * @param string                 $alias
     * @param \ManaPHP\Mongodb\Model $model
     *
     * @return \ManaPHP\Mongodb\Query
     */
    public static function query($alias = null, $model = null)
    {
        if (!$model) {
            $model = Di::getDefault()->getShared(get_called_class());
        }

        return $model->_di->get('ManaPHP\Mongodb\Query')->setModel($model)->setTypes($model->getFieldTypes());
    }

    /**
     * @return static
     */
    public function create()
    {
        $autoIncrementField = $this->getAutoIncrementField();
        if ($autoIncrementField && $this->$autoIncrementField === null) {
            $this->$autoIncrementField = $this->generateAutoIncrementId();
        }

        $fields = $this->getFields();
        foreach ($this->getAutoFilledData(self::OP_CREATE) as $field => $value) {
            /** @noinspection NotOptimalIfConditionsInspection */
            if (!in_array($field, $fields, true) || $this->$field !== null) {
                continue;
            }
            $this->$field = $value;
        }

        $this->validate($fields);

        if ($this->_id) {
            if (is_string($this->_id) && strlen($this->_id) === 24) {
                /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                $this->_id = new ObjectID($this->_id);
            }
        } else {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $this->_id = new ObjectID();
        }

        $allowNull = $this->isAllowNullValue();
        foreach ($this->getFieldTypes() as $field => $type) {
            if ($field === '_id') {
                continue;
            }

            if ($this->$field !== null) {
                if (is_scalar($this->$field)) {
                    $this->$field = $this->normalizeValue($type, $this->$field);
                }
            } else {
                $this->$field = $allowNull ? null : $this->normalizeValue($type, '');
            }
        }

        if ($this->_fireEventCancel('beforeSave') === false || $this->_fireEventCancel('beforeCreate') === false) {
            return $this;
        }

        $fieldValues = [];
        foreach ($fields as $field) {
            $fieldValues[$field] = $this->$field;
        }

        $fieldValues['_id'] = $this->_id;

        foreach ($this->getJsonFields() as $field) {
            if (is_array($this->$field)) {
                $fieldValues[$field] = json_encode($this->$field, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        }

        /**
         * @var \ManaPHP\MongodbInterface $connection
         */
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

        $primaryKeyValuePairs = $this->_getPrimaryKeyValuePairs();

        $fieldTypes = $this->getFieldTypes();
        $fields = array_keys($fieldTypes);

        $changedFields = [];
        foreach ($fields as $field) {
            if ($this->$field === null) {
                /** @noinspection NotOptimalIfConditionsInspection */
                if (isset($snapshot[$field])) {
                    $changedFields[] = $field;
                }
            } elseif (!isset($snapshot[$field])) {
                if (is_scalar($this->$field)) {
                    $this->$field = $this->normalizeValue($fieldTypes[$field], $this->$field);
                }
                $changedFields[] = $field;
            } elseif ($snapshot[$field] !== $this->$field) {
                if (is_scalar($this->$field)) {
                    $this->$field = $this->normalizeValue($fieldTypes[$field], $this->$field);
                }

                /** @noinspection NotOptimalIfConditionsInspection */
                if ($snapshot[$field] !== $this->$field) {
                    $changedFields[] = $field;
                }
            }
        }

        if (!$changedFields) {
            return $this;
        }

        $this->validate($changedFields);

        foreach ($this->getAutoFilledData(self::OP_UPDATE) as $field => $value) {
            if (in_array($field, $fields, true)) {
                $this->$field = $value;
            }
        }

        if ($this->_fireEventCancel('beforeSave') === false || $this->_fireEventCancel('beforeUpdate') === false) {
            return $this;
        }

        $fieldValues = [];
        foreach ($fields as $field) {
            if ($this->$field === null) {
                if (isset($snapshot[$field])) {
                    $fieldValues[$field] = null;
                }
            } elseif (!isset($snapshot[$field]) || $snapshot[$field] !== $this->$field) {
                $fieldValues[$field] = $this->$field;
            }
        }

        foreach ($primaryKeyValuePairs as $key => $value) {
            unset($fieldValues[$key]);
        }

        if (!$fieldValues) {
            return $this;
        }

        foreach ($this->getJsonFields() as $field) {
            if (isset($fieldValues[$field]) && is_array($fieldValues[$field])) {
                $fieldValues[$field] = json_encode($fieldValues[$field], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        }

        $query = static::query(null, $this)->where($primaryKeyValuePairs);
        $query->update($fieldValues);

        $expressionFields = [];
        foreach ($fieldValues as $field => $value) {
            if ($value instanceof ExpressionInterface) {
                $expressionFields[] = $field;
            }
        }

        if ($expressionFields) {
            $expressionFields['_id'] = false;
            if ($rs = $query->select($expressionFields)->execute()) {
                foreach ((array)$rs[0] as $field => $value) {
                    $this->$field = $value;
                }
            }
        }

        $this->_snapshot = $this->toArray();

        $this->_fireEvent('afterUpdate');
        $this->_fireEvent('afterSave');

        return $this;
    }

    /**
     * @param array $pipeline
     * @param array $options
     *
     * @return array
     */
    public static function aggregateEx($pipeline, $options = [])
    {
        $instance = new static();

        return $instance->getConnection()->aggregate($instance->getSource(), $pipeline, $options);
    }

    /**
     * @param array[] $documents
     *
     * @return int
     */
    public static function bulkInsert($documents)
    {
        $instance = new static();

        $autoIncrementField = $instance->getAutoIncrementField();
        $allowNull = $instance->isAllowNullValue();
        $fieldTypes = $instance->getFieldTypes();
        foreach ($documents as $i => $document) {
            if ($autoIncrementField && !isset($document[$autoIncrementField])) {
                $document[$autoIncrementField] = $instance->generateAutoIncrementId();
            }
            foreach ((array)$fieldTypes as $field => $type) {
                if (isset($document[$field])) {
                    $document[$field] = $instance->normalizeValue($type, $document[$field]);
                } elseif ($field !== '_id') {
                    $document[$field] = $allowNull ? null : $instance->normalizeValue($type, '');
                }
            }
            $documents[$i] = $document;
        }

        return $instance->getConnection()->bulkInsert($instance->getSource(), $documents);
    }

    /**
     * @param array $documents
     *
     * @return int
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
            foreach ((array)$document as $field => $value) {
                if ($value === null) {
                    $document[$field] = $allowNull ? null : $instance->normalizeValue($fieldTypes[$field], '');
                } else {
                    $document[$field] = $instance->normalizeValue($fieldTypes[$field], $value);
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
     *
     * @return int
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
            foreach ((array)$fieldTypes as $field => $type) {
                if (isset($document[$field])) {
                    $document[$field] = $instance->normalizeValue($type, $document[$field]);
                } elseif ($field !== '_id') {
                    $document[$field] = $allowNull ? null : $instance->normalizeValue($type, '');
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
     * @param array $document
     *
     * @return int
     */
    public static function insert($document)
    {
        $instance = new static();

        $allowNull = $instance->isAllowNullValue();
        $fieldTypes = $instance->getFieldTypes();
        $autoIncrementField = $instance->getAutoIncrementField();
        if ($autoIncrementField && !isset($document[$autoIncrementField])) {
            $document[$autoIncrementField] = $instance->generateAutoIncrementId();
        }

        foreach ((array)$fieldTypes as $field => $type) {
            if (isset($document[$field])) {
                $document[$field] = $instance->normalizeValue($type, $document[$field]);
            } elseif ($field !== '_id') {
                $document[$field] = $allowNull ? null : $instance->normalizeValue($type, '');
            }
        }
        return $instance->getConnection($document)->insert($instance->getSource($document), $document);
    }

    /**
     * @param int|string|array       $filter
     * @param int|float|string|array $value
     *
     * @return \ManaPHP\Mongodb\Query
     */
    public static function where($filter, $value = null)
    {
        if (is_scalar($filter)) {
            /** @var \ManaPHP\ModelInterface $model */
            $model = Di::getDefault()->getShared(get_called_class());
            return static::query(null, $model)->whereEq($model->getPrimaryKey(), $filter);
        } else {
            return static::query()->where($filter, $value);
        }
    }

    public function __debugInfo()
    {
        $data = parent::__debugInfo();
        if ($data['_id'] === null) {
            unset($data['_id']);
        } elseif (is_object($data['_id'])) {
            $data['_id'] = (string)$data['_id'];
        }

        return $data;
    }
}