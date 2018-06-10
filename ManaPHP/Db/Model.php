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
        $primaryKey = $this->_di->modelsMetadata->getPrimaryKeyAttributes($this);

        if (count($primaryKey) === 1) {
            return $primaryKey[0];
        } else {
            return $primaryKey;
        }
    }

    /**
     * @return array
     */
    public function getFields()
    {
        static $fields = [];

        $className = get_called_class();

        if (!isset($fields[$className])) {
            $properties = array_keys(get_class_vars($className));
            $attributes = $this->_di->modelsMetadata->getAttributes($className);
            $intersect = array_intersect($properties, $attributes);

            $fields[$className] = $intersect ?: $attributes;
        }

        return $fields[$className];
    }

    /**
     * @return array
     */
    public function getIntFields()
    {
        return $this->_di->modelsMetadata->getIntTypeAttributes($this);
    }

    /**
     * @return string|null
     */
    public function getAutoIncrementField()
    {
        return $this->_di->modelsMetadata->getAutoIncrementAttribute($this);
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
     * @param array             $fields
     * @param \ManaPHP\Db\Model $model
     *
     * @return \ManaPHP\Db\Model\CriteriaInterface
     */
    public static function criteria($fields = null, $model = null)
    {
        return Di::getDefault()->get('ManaPHP\Db\Model\Criteria', [$model ?: get_called_class(), $fields]);
    }

    /**
     * Create a criteria for a specific model
     *
     * @param string $alias
     *
     * @return \ManaPHP\Db\Model\QueryInterface
     */
    public static function query($alias = null)
    {
        return Di::getDefault()->get('ManaPHP\Db\Model\Query')->from(get_called_class(), $alias);
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

        $fields = $this->getFields();
        $intFields = $this->getIntFields();

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
                    if (is_string($this->$field)) {
                        if (in_array($field, $intFields, true)) {
                            $this->$field = (int)$this->$field;
                        } elseif (is_float($snapshot[$field])) {
                            $this->$field = (float)$this->$field;
                        }
                    }

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

        if (!$fieldValues) {
            return $this;
        }

        foreach ($this->getAutoFilledData(self::OP_UPDATE) as $field => $value) {
            if (!in_array($field, $fields, true)) {
                continue;
            }

            $this->$field = $value;
            $fieldValues[$field] = $value;
        }

        if ($this->_fireEventCancel('beforeSave') === false || $this->_fireEventCancel('beforeUpdate') === false) {
            return $this;
        }

        $expressionFields = [];
        foreach ($fieldValues as $field => $value) {
            if ($value instanceof ExpressionInterface) {
                $expressionFields[] = $field;
            }
        }

        $criteria = static::criteria(null, $this)->where($this->_getPrimaryKeyValuePairs());
        $criteria->update($fieldValues);
        if ($expressionFields) {
            if ($rs = $criteria->select($expressionFields)->execute()) {
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