<?php

namespace ManaPHP\Mongodb;

use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\NotImplementedException;
use ManaPHP\Exception\PreconditionException;
use ManaPHP\Exception\RuntimeException;
use ManaPHP\Model\ExpressionInterface;
use MongoDB\BSON\ObjectId;

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
        return static::sample()->getConnection($context);
    }

    /**
     * @return string =array_keys(get_object_vars(new static))[$i]
     */
    public function getPrimaryKey()
    {
        static $cached = [];

        $class = static::class;

        if (!isset($cached[$class])) {
            $fields = $this->getFields();

            if (in_array('id', $fields, true)) {
                return $cached[$class] = 'id';
            }

            $tryField = lcfirst(($pos = strrpos($class, '\\')) === false ? $class : substr($class, $pos + 1)) . '_id';
            if (in_array($tryField, $fields, true)) {
                return $cached[$class] = $tryField;
            }

            $source = $this->getSource();
            $tryField = (($pos = strpos($source, '.')) ? substr($source, $pos + 1) : $source) . '_id';
            if (in_array($tryField, $fields, true)) {
                return $cached[$class] = $tryField;
            }

            throw new NotImplementedException(['Primary key of `:model` model can not be inferred', 'model' => $class]);
        }

        return $cached[$class];
    }

    /**
     * @return array =get_object_vars(new static)
     */
    public function getFields()
    {
        static $cached = [];

        $class = static::class;

        if (!isset($cached[$class])) {
            $fieldTypes = $this->getFieldTypes();
            if (isset($fieldTypes['_id']) && $fieldTypes['_id'] === 'objectid') {
                unset($fieldTypes['_id']);
            }
            return $cached[$class] = array_keys($fieldTypes);
        }

        return $cached[$class];
    }

    /**
     * @return array =get_object_vars(new static)
     */
    public function getIntFields()
    {
        static $cached = [];

        $class = static::class;

        if (!isset($cached[$class])) {
            $fields = [];
            foreach ($this->getFieldTypes() as $field => $type) {
                if ($type === 'int') {
                    $fields[] = $field;
                }
            }

            return $cached[$class] = $fields;
        }

        return $cached[$class];
    }

    /**
     * bool, int, float, string, array, objectid
     *
     * @return array =array_keys(get_object_vars(new static))
     */
    public function getFieldTypes()
    {
        static $cached = [];

        $class = static::class;

        if (!isset($cached[$class])) {
            if (!$docs = $this->getConnection()->fetchAll($this->getSource(), [], ['limit' => 1])) {
                throw new RuntimeException(['`:collection` collection has none record', 'collection' => $this->getSource()]);
            }

            $types = [];
            foreach ($docs[0] as $field => $value) {
                $type = gettype($value);
                if ($type === 'integer') {
                    $types[$field] = 'int';
                } elseif ($type === 'string') {
                    $types[$field] = 'string';
                } elseif ($type === 'double') {
                    $types[$field] = 'float';
                } elseif ($type === 'boolean') {
                    $types[$field] = 'bool';
                } elseif ($type === 'array') {
                    $types[$field] = 'array';
                } elseif ($value instanceof ObjectId) {
                    if ($field === '_id') {
                        continue;
                    }
                    $types[$field] = 'objectid';
                } else {
                    throw new RuntimeException(['`:field` field value type can not be infer.', 'field' => $field]);
                }
            }

            $cached[$class] = $types;
        }

        return $cached[$class];
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
    public function getNextAutoIncrementId($step = 1)
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
        } elseif ($type === 'int') {
            return is_int($value) ? $value : (int)$value;
        } elseif ($type === 'float') {
            return is_float($value) ? $value : (float)$value;
        } elseif ($type === 'objectid') {
            return is_scalar($type) ? new ObjectID($value) : $value;
        } elseif ($type === 'bool') {
            return is_bool($value) ? $value : (bool)$value;
        } elseif ($type === 'array') {
            return (array)$value;
        } else {
            throw new InvalidValueException(['`:model` model is not supported `:type` type', 'model' => static::class, 'type' => $type]);
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
            $model = static::sample();
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
            $this->$autoIncrementField = $this->getNextAutoIncrementId();
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
                $this->_id = new ObjectID($this->_id);
            }
        } else {
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

        $this->fireEvent('model:beforeSave');
        $this->fireEvent('model:beforeCreate');

        $fieldValues = [];
        foreach ($fields as $field) {
            $fieldValues[$field] = $this->$field;
        }

        $fieldValues['_id'] = $this->_id;

        foreach ($this->getJsonFields() as $field) {
            if (is_array($this->$field)) {
                $fieldValues[$field] = json_stringify($this->$field);
            }
        }

        /**
         * @var \ManaPHP\MongodbInterface $connection
         */
        $connection = $this->_di->getShared($this->getDb($this));
        $connection->insert($this->getSource($this), $fieldValues);

        $this->fireEvent('model:afterCreate');
        $this->fireEvent('model:afterSave');

        $this->_snapshot = $this->toArray();

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
            throw new PreconditionException(['update failed: `:model` instance is snapshot disabled', 'model' => static::class]);
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

        $this->fireEvent('model:beforeSave');
        $this->fireEvent('model:beforeUpdate');

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
                $fieldValues[$field] = json_stringify($fieldValues[$field]);
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

        $this->fireEvent('model:afterUpdate');
        $this->fireEvent('model:afterSave');

        $this->_snapshot = $this->toArray();

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
        $sample = static::sample();

        return $sample->getConnection()->aggregate($sample->getSource(), $pipeline, $options);
    }

    /**
     * @param array[] $documents
     *
     * @return int
     */
    public static function bulkInsert($documents)
    {
        $sample = static::sample();

        $autoIncrementField = $sample->getAutoIncrementField();
        $allowNull = $sample->isAllowNullValue();
        $fieldTypes = $sample->getFieldTypes();
        foreach ($documents as $i => $document) {
            if ($autoIncrementField && !isset($document[$autoIncrementField])) {
                $document[$autoIncrementField] = $sample->getNextAutoIncrementId();
            }
            foreach ($fieldTypes as $field => $type) {
                if (isset($document[$field])) {
                    $document[$field] = $sample->normalizeValue($type, $document[$field]);
                } elseif ($field !== '_id') {
                    $document[$field] = $allowNull ? null : $sample->normalizeValue($type, '');
                }
            }
            $documents[$i] = $document;
        }

        return $sample->getConnection()->bulkInsert($sample->getSource(), $documents);
    }

    /**
     * @param array $documents
     *
     * @return int
     */
    public static function bulkUpdate($documents)
    {
        $sample = static::sample();

        $primaryKey = $sample->getPrimaryKey();
        $allowNull = $sample->isAllowNullValue();
        $fieldTypes = $sample->getFieldTypes();
        foreach ($documents as $i => $document) {
            if (!isset($document[$primaryKey])) {
                throw new InvalidValueException(['bulkUpdate `:model` model must set primary value', 'model' => static::class]);
            }
            foreach ((array)$document as $field => $value) {
                if ($value === null) {
                    $document[$field] = $allowNull ? null : $sample->normalizeValue($fieldTypes[$field], '');
                } else {
                    $document[$field] = $sample->normalizeValue($fieldTypes[$field], $value);
                }
            }
        }

        /**
         * @var \ManaPHP\MongodbInterface $connection
         */
        $connection = $sample->getConnection();
        return $connection->bulkUpdate($sample->getSource(), $documents, $primaryKey);
    }

    /**
     * @param array[] $documents
     *
     * @return int
     */
    public static function bulkUpsert($documents)
    {
        $sample = static::sample();

        $primaryKey = $sample->getPrimaryKey();
        $allowNull = $sample->isAllowNullValue();
        $fieldTypes = $sample->getFieldTypes();
        foreach ($documents as $i => $document) {
            if (!isset($document[$primaryKey])) {
                $document[$primaryKey] = $sample->getNextAutoIncrementId();
            }
            foreach ($fieldTypes as $field => $type) {
                if (isset($document[$field])) {
                    $document[$field] = $sample->normalizeValue($type, $document[$field]);
                } elseif ($field !== '_id') {
                    $document[$field] = $allowNull ? null : $sample->normalizeValue($type, '');
                }
            }
            $documents[$i] = $document;
        }

        /**
         * @var \ManaPHP\MongodbInterface $connection
         */
        $connection = $sample->getConnection();
        return $connection->bulkUpsert($sample->getSource(), $documents, $primaryKey);
    }

    /**
     * @param array $document
     *
     * @return int
     */
    public static function insert($document)
    {
        $sample = static::sample();

        $allowNull = $sample->isAllowNullValue();
        $fieldTypes = $sample->getFieldTypes();
        $autoIncrementField = $sample->getAutoIncrementField();
        if ($autoIncrementField && !isset($document[$autoIncrementField])) {
            $document[$autoIncrementField] = $sample->getNextAutoIncrementId();
        }

        foreach ($fieldTypes as $field => $type) {
            if (isset($document[$field])) {
                $document[$field] = $sample->normalizeValue($type, $document[$field]);
            } elseif ($field !== '_id') {
                $document[$field] = $allowNull ? null : $sample->normalizeValue($type, '');
            }
        }
        return $sample->getConnection($document)->insert($sample->getSource($document), $document);
    }

    /**
     * @param int|string|array $filters =get_object_vars(new static)
     *
     * @return \ManaPHP\Mongodb\Query
     */
    public static function where($filters)
    {
        if (is_scalar($filters)) {
            $sample = static::sample();
            return static::query(null, $sample)->whereEq($sample->getPrimaryKey(), $filters);
        } else {
            return static::query()->where($filters);
        }
    }

    /**
     * @param array $filters =get_object_vars(new static)
     *
     * @return \ManaPHP\Mongodb\Query
     */
    public static function search($filters)
    {
        return static::query()->search($filters);
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