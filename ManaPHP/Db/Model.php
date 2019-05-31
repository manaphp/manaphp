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
    public static function connection($context = null)
    {
        return (new static())->getConnection($context);
    }

    /**
     * @return string|array
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

            $tryField = $this->getSource() . '_id';
            if (in_array($tryField, $fields, true)) {
                return $cached[$class] = $tryField;
            }

            $primaryKey = $this->_di->modelsMetadata->getPrimaryKeyAttributes($this);
            return $cached[$class] = count($primaryKey) === 1 ? $primaryKey[0] : $primaryKey;
        }

        return $cached[$class];
    }

    /**
     * @return array
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
    public function getNextAutoIncrementId($step = 1)
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
            $model = Di::getDefault()->getShared(static::class);
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

        $this->fireEvent('model:beforeSave');
        $this->fireEvent('model:beforeCreate');

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
                $fieldValues[$field] = json_encode($this->$field, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        }

        /**
         * @var \ManaPHP\DbInterface $connection
         */
        $connection = $this->_di->getShared($this->getDb($this));

        if ($autoIncrementField && $this->$autoIncrementField === null) {
            $this->$autoIncrementField = (int)$connection->insert($this->getSource($this), $fieldValues, true);
        } else {
            $connection->insert($this->getSource($this), $fieldValues);
        }

        if ($defaultValueFields) {
            if ($r = static::query(null, $this)->select($defaultValueFields)->where($this->_getPrimaryKeyValuePairs())->fetch(true)) {
                foreach ($r[0] as $field => $value) {
                    $this->$field = $value;
                }
            }
        }

        $this->_snapshot = $this->toArray();

        $this->fireEvent('model:afterCreate');
        $this->fireEvent('model:afterSave');

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

        $this->fireEvent('model:afterUpdate');
        $this->fireEvent('model:afterSave');

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
        return $model->getConnection($bind)->insertBySql('INSERT' . " INTO [$table] " . $sql, $bind);
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
        return $model->getConnection($bind)->deleteBySql('DELETE' . " FROM [$table] WHERE " . $sql, $bind);
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
        return $model->getConnection($bind)->updateBySql('UPDATE' . " [$table] SET " . $sql, $bind);
    }

    /**
     * @param array $record
     *
     * @return int
     */
    public static function insert($record)
    {
        $instance = new static();
        if ($fields = array_diff(array_keys($record), $instance->_di->modelsMetadata->getAttributes($instance))) {
            $instance->_di->logger->debug(['insert `:1` table skip fields: :2', $instance->getSource(), array_values($fields)]);

            foreach ($fields as $field) {
                unset($record[$field]);
            }
        }

        $instance->getConnection($record)->insert($instance->getSource($record), $record);

        return 1;
    }

    /**
     * @param int|string|array       $filter
     * @param int|float|string|array $value
     *
     * @return \ManaPHP\Db\Query
     */
    public static function where($filter, $value = null)
    {
        if (is_scalar($filter)) {
            /** @var \ManaPHP\ModelInterface $model */
            $model = Di::getDefault()->getShared(static::class);
            return static::query(null, $model)->whereEq($model->getPrimaryKey(), $filter);
        } else {
            return static::query()->where($filter, $value);
        }
    }

    /**
     * @param array $filters
     *
     * @return \ManaPHP\Db\Query
     */
    public static function whereSearch($filters)
    {
        return static::query()->where($filters);
    }
}