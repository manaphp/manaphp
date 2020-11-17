<?php

namespace ManaPHP\Data\Db;

use ManaPHP\Data\Model\ExpressionInterface;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;

class Model extends \ManaPHP\Data\Model implements ModelInterface
{
    /**
     * Gets the connection used to crud data to the model
     *
     * @return string
     */
    public function getDb()
    {
        return 'db';
    }

    /**
     * @param mixed $context =get_object_vars(new static)
     *
     * @return \ManaPHP\Data\DbInterface
     */
    public static function connection($context = null)
    {
        list($db) = static::sample()->getUniqueShard($context);

        return static::sample()->getShared($db);
    }

    /**
     * @return string =key(get_object_vars(new static))
     */
    public function getPrimaryKey()
    {
        static $cached = [];

        $class = static::class;

        if (!isset($cached[$class])) {
            if ($primaryKey = $this->_inferPrimaryKey($class)) {
                return $cached[$class] = $primaryKey;
            } else {
                /** @var \ManaPHP\Data\Db\Model\MetadataInterface $modelsMetadata */
                $modelsMetadata = $this->getShared('modelsMetadata');

                $primaryKeys = $modelsMetadata->getPrimaryKeyAttributes($this);
                if (count($primaryKeys) !== 1) {
                    throw new NotSupportedException('only support one primary key');
                }
                return $cached[$class] = $primaryKeys[0];
            }
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
            $fields = [];
            foreach (get_class_vars($class) as $field => $value) {
                if ($value === null && $field[0] !== '_') {
                    $fields[] = $field;
                }
            }

            /** @var \ManaPHP\Data\Db\Model\MetadataInterface $modelsMetadata */
            $modelsMetadata = $this->getShared('modelsMetadata');

            $cached[$class] = $fields ?: $modelsMetadata->getAttributes($this);
        }

        return $cached[$class];
    }

    /**
     * @return array =get_object_vars(new static)
     */
    public function getIntFields()
    {
        /** @var \ManaPHP\Data\Db\Model\MetadataInterface $modelsMetadata */
        $modelsMetadata = $this->getShared('modelsMetadata');

        return $modelsMetadata->getIntTypeAttributes($this);
    }

    /**
     * @param int $step
     *
     * @return int
     */
    public function getNextAutoIncrementId($step = 1)
    {
        return null;
    }

    /**
     * @return \ManaPHP\Data\Db\Query <static>
     */
    public function newQuery()
    {
        return $this->getInstance('ManaPHP\Data\Db\Query')->setModel($this);
    }

    /**
     * Inserts a model instance. If the instance already exists in the persistence it will throw an exception
     *
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
            if ($this->$field === null) {
                $this->$field = $value;
            }
        }

        $this->validate($fields);

        list($db, $table) = $this->getUniqueShard($this);

        $this->fireEvent('model:saving');
        $this->fireEvent('model:creating');

        $fieldValues = [];
        $defaultValueFields = [];
        foreach ($fields as $field) {
            if ($this->$field !== null) {
                $fieldValues[$field] = $this->$field;
            } elseif ($field !== $autoIncrementField) {
                $defaultValueFields[] = $field;
            }
        }

        foreach ($this->getJsonFields() as $field) {
            if (is_array($this->$field)) {
                $fieldValues[$field] = json_stringify($this->$field);
            }
        }

        /** @var \ManaPHP\Data\DbInterface $db */
        $db = $this->getShared($db);
        if ($autoIncrementField && $this->$autoIncrementField === null) {
            $this->$autoIncrementField = (int)$db->insert($table, $fieldValues, true);
        } else {
            $db->insert($table, $fieldValues);
        }

        if ($defaultValueFields) {
            $primaryKey = $this->getPrimaryKey();
            $query = $this->newQuery()->select($defaultValueFields)->whereEq($primaryKey, $this->$primaryKey);
            if ($r = $query->execute()) {
                foreach ($r[0] as $field => $value) {
                    $this->$field = $value;
                }
            }
        }

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
        $primaryKey = $this->getPrimaryKey();

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

        $fields = $this->getFields();

        foreach ($fields as $field) {
            if ($this->$field === null) {
                null;
            } elseif (!isset($snapshot[$field])) {
                null;
            } elseif ($snapshot[$field] !== $this->$field) {
                if ((is_string($this->$field) && !is_string($snapshot[$field]))
                    && (string)$snapshot[$field] === $this->$field
                ) {
                    $this->$field = $snapshot[$field];
                }
            }
        }

        $this->validate();

        if (!$this->hasChanged($fields)) {
            return $this;
        }

        foreach ($this->getAutoFilledData(self::OP_UPDATE) as $field => $value) {
            $this->$field = $value;
        }

        list($db, $table) = $this->getUniqueShard($this);

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

        foreach ($this->getJsonFields() as $field) {
            if (isset($fieldValues[$field]) && is_array($fieldValues[$field])) {
                $fieldValues[$field] = json_stringify($fieldValues[$field]);
            }
        }

        $bind = [];
        $expressionFields = [];
        foreach ($fieldValues as $field => $value) {
            if ($value instanceof ExpressionInterface) {
                $expressionFields[] = $field;
                $compiled = $value->compile($this, $field);

                $fieldValues[] = $compiled[0];
                if (count($compiled) !== 1) {
                    unset($compiled[0]);
                    $bind = $bind ? array_merge($bind, $compiled) : $compiled;
                }
                unset($fieldValues[$field]);
            }
        }

        /** @var \ManaPHP\Data\DbInterface $db */
        $db = $this->getShared($db);
        $db->update($table, $fieldValues, [$primaryKey => $this->$primaryKey], $bind);

        if ($expressionFields) {
            $query = $this->newQuery()->select($expressionFields)->whereEq($primaryKey, $this->$primaryKey);
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
     * @param array|string $sql
     *
     * @return int
     */
    public static function insertBySql($sql)
    {
        if (is_array($sql)) {
            $bind = $sql;
            unset($bind[0]);
            $sql = $sql[0];
        } else {
            $bind = [];
        }

        $sample = static::sample();

        list($db, $table) = $sample->getUniqueShard($bind);

        /** @var \ManaPHP\Data\DbInterface $db */
        $db = static::sample()->getShared($db);

        return $db->insertBySql($table, $sql, $bind);
    }

    /**
     * @param array|string $sql
     *
     * @return int
     */
    public static function deleteBySql($sql)
    {
        if (is_array($sql)) {
            $bind = $sql;
            unset($bind[0]);
            $sql = $sql[0];
        } else {
            $bind = [];
        }

        $shards = static::sample()->getMultipleShards($bind);

        $affected_count = 0;
        foreach ($shards as $db => $tables) {
            /** @var \ManaPHP\Data\DbInterface $db */
            $db = static::sample()->getShared($db);

            foreach ($tables as $table) {
                $affected_count += $db->deleteBySql($table, $sql, $bind);
            }
        }

        return $affected_count;
    }

    /**
     * @param array|string $sql
     *
     * @return int
     */
    public static function updateBySql($sql)
    {
        if (is_array($sql)) {
            $bind = $sql;
            unset($bind[0]);
            $sql = $sql[0];
        } else {
            $bind = [];
        }

        $shards = static::sample()->getMultipleShards($bind);

        $affected_count = 0;
        foreach ($shards as $db => $tables) {
            /** @var \ManaPHP\Data\DbInterface $db */
            $db = static::sample()->getShared($db);

            foreach ($tables as $table) {
                $affected_count += $db->updateBySql($table, $sql, $bind);
            }
        }

        return $affected_count;
    }

    /**
     * @param array $record =get_object_vars(new static)
     *
     * @return int
     */
    public static function insert($record)
    {
        $sample = static::sample();

        list($db, $table) = $sample->getUniqueShard($record);
        /** @var \ManaPHP\Data\Db\Model\MetadataInterface $modelsMetadata */
        /** @var \ManaPHP\Logging\LoggerInterface $logger */
        $modelsMetadata = $sample->getShared('modelsMetadata');
        $logger = $sample->getShared('logger');

        if ($fields = array_diff(array_keys($record), $modelsMetadata->getAttributes($sample))) {
            $logger->debug(['insert `:1` table skip fields: :2', $table, array_values($fields)]);

            foreach ($fields as $field) {
                unset($record[$field]);
            }
        }

        /** @var \ManaPHP\Data\DbInterface $db */
        $db = static::sample()->getShared($db);
        $db->insert($table, $record);

        return 1;
    }
}
