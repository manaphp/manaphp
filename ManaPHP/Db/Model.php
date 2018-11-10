<?php

namespace ManaPHP\Db;

use ManaPHP\Di;
use ManaPHP\Exception\PreconditionException;
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
     * @param mixed $context
     *
     * @return string
     */
    public function getDb($context = null)
    {
        return 'db';
    }

    /**
     * @param mixed $context
     *
     * @return \ManaPHP\DbInterface
     */
    public function getConnection($context = null)
    {
        return $this->_di->getShared($this->getDb($context));
    }

    /**
     * @param mixed $context
     *
     * @return \ManaPHP\DbInterface
     */
    public function getMasterConnection($context = null)
    {
        return $this->getConnection($context)->getMasterConnection();
    }

    /**
     * @param mixed $context
     *
     * @return \ManaPHP\DbInterface
     */
    public function getSlaveConnection($context = null)
    {
        return $this->getConnection($context)->getMasterConnection();
    }

    /**
     * @return string|array
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

            $tryField = $this->getSource() . '_id';
            if (in_array($tryField, $fields, true)) {
                return $cached[$calledClass] = $tryField;
            }

            $primaryKey = $this->_di->modelsMetadata->getPrimaryKeyAttributes($this);
            return $cached[$calledClass] = count($primaryKey) === 1 ? $primaryKey[0] : $primaryKey;
        }

        return $cached[$calledClass];
    }

    /**
     * @return array
     */
    public function getFields()
    {
        static $cached = [];

        $className = get_called_class();
        if (!isset($cached[$className])) {
            $fields = [];
            foreach (get_class_vars($className) as $field => $value) {
                if ($value === null && $field[0] !== '_') {
                    $fields[] = $field;
                }
            }

            $cached[$className] = $fields;
        }

        return $cached[$className];
    }

    /**
     * @return array
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
    public function generateAutoIncrementId($step = 1)
    {
        return null;
    }

    /**
     * @param string         $alias
     * @param \ManaPHP\Model $model
     *
     * @return \ManaPHP\Db\Query
     */
    public static function query($alias = null, $model = null)
    {
        if (!$model) {
            $model = Di::getDefault()->getShared(get_called_class());
        }

        $query = $model->_di->get('ManaPHP\Db\Query')->setModel($model);
        if ($alias) {
            $query->from(get_class($model), $alias);
        }

        return $query;
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

        if ($this->_fireEventCancel('beforeSave') === false || $this->_fireEventCancel('beforeCreate') === false) {
            return $this;
        }

        $fieldValues = [];
        foreach ($fields as $field) {
            if ($this->$field !== null) {
                $fieldValues[$field] = $this->$field;
            }
        }

        foreach ($this->getJsonFields() as $field) {
            if (is_array($this->$field)) {
                $fieldValues[$field] = json_encode($this->$field, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        }

        /**
         * @var \ManaPHP\DbInterface $connection
         */
        $connection = $this->_di->getShared($this->getDb($this));
        $connection->insert($this->getSource($this), $fieldValues);

        if ($autoIncrementField && $this->$autoIncrementField === null) {
            $this->$autoIncrementField = $connection->lastInsertId();
        }

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

        $primaryKeyValuePairs = $this->getPrimaryKeyValuePairs();

        $fields = $this->getFields();

        $changedFields = [];
        foreach ($fields as $field) {
            if ($this->$field === null) {
                if (isset($snapshot[$field])) {
                    $changedFields[] = $field;
                }
            } else {
                if (!isset($snapshot[$field])) {
                    $changedFields[] = $field;
                } elseif ($snapshot[$field] !== $this->$field) {
                    if (is_string($this->$field) && !is_string($snapshot[$field]) && (string)$snapshot[$field] === $this->$field) {
                        $this->$field = $snapshot[$field];
                    } else {
                        $changedFields[] = $field;
                    }
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
            } else {
                if (!isset($snapshot[$field]) || $snapshot[$field] !== $this->$field) {
                    $fieldValues[$field] = $this->$field;
                }
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

        if ($expressionFields && $rs = $query->select($expressionFields)->fetch(true)) {
            foreach ((array)$rs[0] as $field => $value) {
                $this->$field = $value;
            }
        }

        $this->_snapshot = $this->toArray();

        $this->_fireEvent('afterUpdate');
        $this->_fireEvent('afterSave');

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

        $model = new static;

        $table = $model->getSource($bind);
        /** @noinspection SqlDialectInspection */
        /** @noinspection SqlNoDataSourceInspection */
        return $model->getMasterConnection($bind)->execute("INSERT INTO [$table] " . $sql, $bind);
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

        $model = new static;

        $table = $model->getSource($bind);
        /** @noinspection SqlDialectInspection */
        /** @noinspection SqlNoDataSourceInspection */
        return $model->getMasterConnection($bind)->execute("DELETE FROM [$table] WHERE " . $sql, $bind);
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

        $model = new static;

        $table = $model->getSource($bind);
        return $model->getMasterConnection($bind)->execute("UPDATE [$table] SET " . $sql, $bind);
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
        if ($fields = array_diff(array_keys($record), $instance->_di->modelsMetadata->getAttributes($instance))) {
            $instance->logger->debug(['insert `:1` table skip fields: :2', $instance->getSource(), array_values($fields)]);

            foreach ($fields as $field) {
                unset($record[$field]);
            }
        }
        return $instance->getMasterConnection($record)->insert($instance->getSource($record), $record, $instance->getPrimaryKey(), $skipIfExists);
    }

    /**
     * @param array $records
     * @param bool  $skipIfExists
     *
     * @return int
     */
    public static function bulkInsert($records, $skipIfExists = false)
    {
        if (!$records) {
            return 0;
        }

        $instance = new static();
        if ($fields = array_diff(array_keys($records[0]), $instance->_di->modelsMetadata->getAttributes($instance))) {
            $instance->logger->debug(['bulkInsert `:1` table skip fields: :2', $instance->getSource(), array_values($fields)]);

            foreach ($records as $k => $record) {
                foreach ($fields as $field) {
                    unset($record[$field]);
                }
                $records[$k] = $record;
            }
        }

        return $instance->getMasterConnection()->bulkInsert($instance->getSource(), $records, $instance->getPrimaryKey(), $skipIfExists);
    }
}