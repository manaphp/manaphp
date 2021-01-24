<?php

namespace ManaPHP\Data\Mongodb;

use ManaPHP\Data\Model\ExpressionInterface;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotImplementedException;
use ManaPHP\Exception\RuntimeException;
use MongoDB\BSON\ObjectId;

class Model extends \ManaPHP\Data\Model
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
     * @return string
     */
    public function db()
    {
        return 'mongodb';
    }

    /**
     * @param bool $allow
     *
     * @return void
     */
    public static function setDefaultAllowNullValue($allow)
    {
        self::$_defaultAllowNullValue = $allow;
    }

    /**
     * @param mixed $context =model_var(new static)
     *
     * @return \ManaPHP\Data\MongodbInterface
     */
    public static function connection($context = null)
    {
        list($db) = static::sample()->getUniqueShard($context);
        return static::sample()->getShared($db);
    }

    /**
     * @return string =model_field(new static)
     */
    public function primaryKey()
    {
        static $cached = [];

        $class = static::class;

        if (!isset($cached[$class])) {
            if ($primaryKey = $this->_inferPrimaryKey($class)) {
                return $cached[$class] = $primaryKey;
            } else {
                throw new NotImplementedException(['Primary key of `%s` model can not be inferred', $class]);
            }
        }

        return $cached[$class];
    }

    /**
     * @return array =model_fields(new static)
     */
    public function fields()
    {
        static $cached = [];

        $class = static::class;

        if (!isset($cached[$class])) {
            $fieldTypes = $this->fieldTypes();
            if (isset($fieldTypes['_id']) && $fieldTypes['_id'] === 'objectid') {
                unset($fieldTypes['_id']);
            }
            return $cached[$class] = array_keys($fieldTypes);
        }

        return $cached[$class];
    }

    /**
     * @return array =model_fields(new static)
     */
    public function intFields()
    {
        static $cached = [];

        $class = static::class;

        if (!isset($cached[$class])) {
            $fields = [];
            foreach ($this->fieldTypes() as $field => $type) {
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
     * @return array =model_var(new static)
     */
    public function fieldTypes()
    {
        static $cached = [];

        $class = static::class;

        if (!isset($cached[$class])) {
            list($db, $collection) = $this->getAnyShard();

            /** @var \ManaPHP\Data\MongodbInterface $mongodb */
            $mongodb = $this->getShared($db);
            if (!$docs = $mongodb->fetchAll($collection, [], ['limit' => 1])) {
                throw new RuntimeException(['`:collection` collection has none record', 'collection' => $collection]);
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
     * @param \ManaPHP\Data\MongodbInterface $mongodb
     * @param string                         $source
     *
     * @return bool
     */
    protected function _createAutoIncrementIndex($mongodb, $source)
    {
        $autoIncField = $this->autoIncrementField();

        if ($pos = strpos($source, '.')) {
            $db = substr($source, 0, $pos);
            $collection = substr($source, $pos + 1);
        } else {
            $db = null;
            $collection = $source;
        }

        $collection = $mongodb->getPrefix() . $collection;

        $command = [
            'createIndexes' => $collection,
            'indexes'       => [
                [
                    'key'    => [
                        $autoIncField => 1
                    ],
                    'unique' => true,
                    'name'   => $autoIncField
                ]
            ]
        ];

        $mongodb->command($command, $db);

        return true;
    }

    /**
     * @param int $step
     *
     * @return int
     */
    public function getNextAutoIncrementId($step = 1)
    {
        list($db, $source) = $this->getUniqueShard($this);

        /** @var \ManaPHP\Data\MongodbInterface $mongodb */
        $mongodb = $this->getShared($db);

        if ($pos = strpos($source, '.')) {
            $db = substr($source, 0, $pos);
            $collection = substr($source, $pos + 1);
        } else {
            $db = null;
            $collection = $source;
        }

        $collection = $mongodb->getPrefix() . $collection;

        $command = [
            'findAndModify' => 'auto_increment_id',
            'query'         => ['_id' => $collection],
            'update'        => ['$inc' => ['current_id' => $step]],
            'new'           => true,
            'upsert'        => true
        ];

        $id = $mongodb->command($command, $db)[0]['value']['current_id'];

        if ($id === $step) {
            $this->_createAutoIncrementIndex($mongodb, $source);
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
            return is_scalar($value) ? new ObjectID($value) : $value;
        } elseif ($type === 'bool') {
            return is_bool($value) ? $value : (bool)$value;
        } elseif ($type === 'array') {
            return (array)$value;
        } else {
            throw new MisuseException(['`%s` model is not supported `%s` type', static::class, $type]);
        }
    }

    /**
     * @return \ManaPHP\Data\Mongodb\Query <static>
     */
    public function newQuery()
    {
        return $this->getInstance('ManaPHP\Data\Mongodb\Query')->setModel($this);
    }

    /**
     * @return static
     */
    public function create()
    {
        $autoIncrementField = $this->autoIncrementField();
        if ($autoIncrementField && $this->$autoIncrementField === null) {
            $this->$autoIncrementField = $this->getNextAutoIncrementId();
        }

        $fields = $this->fields();
        foreach ($this->getAutoCreatedData() as $field => $value) {
            if ($this->$field === null) {
                $this->$field = $value;
            }
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
        foreach ($this->fieldTypes() as $field => $type) {
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

        list($db, $collection) = $this->getUniqueShard($this);

        $this->fireEvent('model:saving');
        $this->fireEvent('model:creating');

        $fieldValues = [];
        foreach ($fields as $field) {
            $fieldValues[$field] = $this->$field;
        }

        $fieldValues['_id'] = $this->_id;

        foreach ($this->jsonFields() as $field) {
            if (is_array($this->$field)) {
                $fieldValues[$field] = json_stringify($this->$field);
            }
        }

        /** @var \ManaPHP\Data\MongodbInterface $mongodb */
        $mongodb = $this->getShared($db);
        $mongodb->insert($collection, $fieldValues);

        $this->fireEvent('model:created');
        $this->fireEvent('model:saved');

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
        $primaryKey = $this->primaryKey();

        if ($this->$primaryKey === null) {
            throw new MisuseException('missing primary key value');
        }

        if (!isset($snapshot[$primaryKey])) {
            $this->_snapshot[$primaryKey] = $this->$primaryKey;
        }

        $snapshot = $this->_snapshot;

        /** @noinspection TypeUnsafeComparisonInspection */
        if ($this->$primaryKey != $snapshot[$primaryKey]) {
            throw new MisuseException('updating model primary key value is not support');
        }

        $fieldTypes = $this->fieldTypes();
        $fields = $this->fields();

        foreach ($fields as $field) {
            if ($this->$field === null) {
                null;
            } elseif (!isset($snapshot[$field])) {
                if (is_scalar($this->$field)) {
                    $this->$field = $this->normalizeValue($fieldTypes[$field], $this->$field);
                }
            } elseif ($snapshot[$field] !== $this->$field) {
                if (is_scalar($this->$field)) {
                    $this->$field = $this->normalizeValue($fieldTypes[$field], $this->$field);
                }
            }
        }

        $this->validate();

        if (!$this->hasChanged($fields)) {
            return $this;
        }

        foreach ($this->getAutoUpdatedData() as $field => $value) {
            $this->$field = $value;
        }

        list($db, $collection) = $this->getUniqueShard($this);

        $this->fireEvent('model:saving');
        $this->fireEvent('model:updating');

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

        foreach ($this->jsonFields() as $field) {
            if (isset($fieldValues[$field]) && is_array($fieldValues[$field])) {
                $fieldValues[$field] = json_stringify($fieldValues[$field]);
            }
        }

        $expressions = [];
        $expressionFields = [];
        foreach ($fieldValues as $field => $value) {
            if ($value instanceof ExpressionInterface) {
                $expressionFields[] = $field;
                $expressions[$field] = $value;
                unset($fieldValues[$field]);
            }
        }

        if ($expressions) {
            $fieldValues = ['$set' => $fieldValues];
            foreach ($expressions as $field => $value) {
                $compiled = $value->compile($this, $field);
                $fieldValues = $fieldValues ? array_merge_recursive($fieldValues, $compiled) : $compiled;
            }
        }

        /** @var \ManaPHP\Data\MongodbInterface $mongodb */
        $mongodb = $this->getShared($db);
        $mongodb->update($collection, $fieldValues, [$primaryKey => $this->$primaryKey]);

        if ($expressionFields) {
            $expressionFields['_id'] = false;
            $query = $this->newQuery()->where([$primaryKey => $this->$primaryKey])->select($expressionFields);
            if ($rs = $query->execute()) {
                foreach ((array)$rs[0] as $field => $value) {
                    $this->$field = $value;
                }
            }
        }

        $this->fireEvent('model:updated');
        $this->fireEvent('model:saved');

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

        list($db, $collection) = $sample->getUniqueShard([]);

        /** @var \ManaPHP\Data\MongodbInterface $mongodb */
        $mongodb = static::sample()->getShared($db);
        return $mongodb->aggregate($collection, $pipeline, $options);
    }

    /**
     * @param array $document
     *
     * @return array
     */
    public function normalizeDocument($document)
    {
        $sample = static::sample();

        $allowNull = $sample->isAllowNullValue();
        $fieldTypes = $sample->fieldTypes();
        $autoIncrementField = $sample->autoIncrementField();
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

        return $document;
    }

    /**
     * @param array[] $documents
     *
     * @return int
     */
    public static function bulkInsert($documents)
    {
        if (!$documents) {
            return 0;
        }

        $sample = static::sample();

        foreach ($documents as $i => $document) {
            $documents[$i] = $sample->normalizeDocument($document);
        }

        list($db, $collection) = $sample->getUniqueShard([]);

        /** @var \ManaPHP\Data\MongodbInterface $mongodb */
        $mongodb = static::sample()->getShared($db);
        return $mongodb->bulkInsert($collection, $documents);
    }

    /**
     * @param array $documents
     *
     * @return int
     */
    public static function bulkUpdate($documents)
    {
        if (!$documents) {
            return 0;
        }

        $sample = static::sample();

        $primaryKey = $sample->primaryKey();
        foreach ($documents as $i => $document) {
            if (!isset($document[$primaryKey])) {
                throw new MisuseException(['bulkUpdate `%s` model must set primary value', static::class]);
            }
            $documents[$i] = $sample->normalizeDocument($document);
        }

        $shards = $sample->getAllShards();

        $affected_count = 0;
        foreach ($shards as $db => $collections) {
            /** @var \ManaPHP\Data\MongodbInterface $mongodb */
            $mongodb = static::sample()->getShared($db);
            foreach ($collections as $collection) {
                $affected_count += $mongodb->bulkUpdate($collection, $documents, $primaryKey);
            }
        }

        return $affected_count;
    }

    /**
     * @param array[] $documents
     *
     * @return int
     */
    public static function bulkUpsert($documents)
    {
        if (!$documents) {
            return 0;
        }

        $sample = static::sample();

        foreach ($documents as $i => $document) {
            $documents[$i] = $sample->normalizeDocument($document);
        }

        list($db, $collection) = $sample->getUniqueShard([]);

        /** @var \ManaPHP\Data\MongodbInterface $mongodb */
        $mongodb = static::sample()->getShared($db);
        return $mongodb->bulkUpsert($collection, $documents, $sample->primaryKey());
    }

    /**
     * @param array $record =model_var(new static)
     *
     * @return int
     */
    public static function insert($record)
    {
        $sample = static::sample();

        $record = $sample->normalizeDocument($record);

        list($db, $collection) = $sample->getUniqueShard($record);

        /** @var \ManaPHP\Data\MongodbInterface $mongodb */
        $mongodb = static::sample()->getShared($db);
        $mongodb->insert($collection, $record);

        return 1;
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