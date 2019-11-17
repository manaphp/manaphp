<?php

namespace ManaPHP\Db;

use ManaPHP\Di;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Model\ExpressionInterface;

/**
 * Class ManaPHP\Db\Model
 *
 * @package model
 *
 */
class Model extends \ManaPHP\Model implements ModelInterface
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
     * @return \ManaPHP\DbInterface
     */
    public static function connection($context = null)
    {
        list($db) = static::sample()->getUniqueShard($context);

        return Di::getDefault()->getShared($db);
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
                $primaryKeys = $this->_di->modelsMetadata->getPrimaryKeyAttributes($this);
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

            $cached[$class] = $fields ?: $this->_di->modelsMetadata->getAttributes($this);
        }

        return $cached[$class];
    }

    /**
     * @return array =get_object_vars(new static)
     */
    public function getIntFields()
    {
        return $this->_di->modelsMetadata->getIntTypeAttributes($this);
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
     * @return \ManaPHP\Db\Query <static>
     */
    public function newQuery()
    {
        return $this->_di->get('ManaPHP\Db\Query')->setModel($this);
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
            /** @noinspection NotOptimalIfConditionsInspection */
            if (!in_array($field, $fields, true) || $this->$field !== null) {
                continue;
            }
            $this->$field = $value;
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

        /** @var \ManaPHP\DbInterface $db */
        $db = $this->_di->getShared($db);
        if ($autoIncrementField && $this->$autoIncrementField === null) {
            $this->$autoIncrementField = (int)$db->insert($table, $fieldValues, true);
        } else {
            $db->insert($table, $fieldValues);
        }

        if ($defaultValueFields) {
            $primaryKey = $this->getPrimaryKey();
            if ($r = $this->newQuery()->select($defaultValueFields)->whereEq($primaryKey, $this->$primaryKey)->fetch(true)) {
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
        $snapshot = $this->_snapshot;

        $primaryKey = $this->getPrimaryKey();

        $fields = $this->getFields();

        $changedFields = [];
        foreach ($fields as $field) {
            if ($this->$field === null) {
                /** @noinspection NotOptimalIfConditionsInspection */
                if (isset($snapshot[$field])) {
                    $changedFields[] = $field;
                }
            } elseif (!isset($snapshot[$field])) {
                $changedFields[] = $field;
            } elseif ($snapshot[$field] !== $this->$field) {
                if (is_string($this->$field) && !is_string($snapshot[$field]) && (string)$snapshot[$field] === $this->$field) {
                    $this->$field = $snapshot[$field];
                } else {
                    $changedFields[] = $field;
                }
            }
        }

        if (!$changedFields) {
            return $this;
        }

        $this->validate($changedFields);

        //Model::validate() method maybe modify data, e.g. decimal data type of db
        foreach ($changedFields as $key => $field) {
            if (isset($snapshot[$field]) && $snapshot[$field] === $this->$field) {
                unset($changedFields[$key]);
            }
        }

        if (!$changedFields) {
            return $this;
        }

        foreach ($this->getAutoFilledData(self::OP_UPDATE) as $field => $value) {
            if (in_array($field, $fields, true)) {
                $this->$field = $value;
            }
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

        unset($fieldValues[$primaryKey]);

        if (!$fieldValues) {
            return $this;
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

        /** @var \ManaPHP\DbInterface $db */
        $db = $this->_di->getShared($db);
        $db->update($table, $fieldValues, [$primaryKey => $this->$primaryKey], $bind);

        if ($expressionFields && $rs = $this->newQuery()->select($expressionFields)->whereEq($primaryKey, $this->$primaryKey)->fetch(true)) {
            foreach ((array)$rs[0] as $field => $value) {
                $this->$field = $value;
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

        /** @var \ManaPHP\DbInterface $db */
        $db = Di::getDefault()->getShared($db);

        $normalized_table = '[' . str_replace('.', '].[', $table) . ']';
        return $db->insertBySql(/**@lang text */ "INSERT INTO $normalized_table $sql", $bind);
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
            /** @var \ManaPHP\DbInterface $db */
            $db = Di::getDefault()->getShared($db);

            foreach ($tables as $table) {
                $normalized_table = '[' . str_replace('.', '].[', $table) . ']';
                $affected_count += $db->deleteBySql(/**@lang text */ "DELETE FROM $normalized_table WHERE $sql", $bind);
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
            /** @var \ManaPHP\DbInterface $db */
            $db = Di::getDefault()->getShared($db);

            foreach ($tables as $table) {
                $normalized_table = '[' . str_replace('.', '].[', $table) . ']';
                $affected_count += $db->updateBySql(/**@lang text */ "UPDATE $normalized_table SET $sql", $bind);
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

        if ($fields = array_diff(array_keys($record), $sample->_di->modelsMetadata->getAttributes($sample))) {
            $sample->_di->logger->debug(['insert `:1` table skip fields: :2', $table, array_values($fields)]);

            foreach ($fields as $field) {
                unset($record[$field]);
            }
        }

        /** @var \ManaPHP\DbInterface $db */
        $db = Di::getDefault()->getShared($db);
        $db->insert($table, $record);

        return 1;
    }
}