<?php

namespace ManaPHP\Db;

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
        return static::sample()->getConnection($context);
    }

    /**
     * @return string|array =array_keys(get_object_vars(new static))[$i]
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
     * @param string         $alias
     * @param \ManaPHP\Model $model
     *
     * @return \ManaPHP\Db\Query
     */
    public static function query($alias = null, $model = null)
    {
        if (!$model) {
            $model = static::sample();
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

        /** @var \ManaPHP\DbInterface $connection */
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

        if ($expressionFields && $rs = $query->select($expressionFields)->fetch(true)) {
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

        $table = $sample->getSource($bind);
        return $sample->getConnection($bind)->insertBySql('INSERT' . " INTO [$table] " . $sql, $bind);
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

        $sample = static::sample();

        $table = $sample->getSource($bind);
        return $sample->getConnection($bind)->deleteBySql('DELETE' . " FROM [$table] WHERE " . $sql, $bind);
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

        $sample = static::sample();

        $table = $sample->getSource($bind);
        return $sample->getConnection($bind)->updateBySql('UPDATE' . " [$table] SET " . $sql, $bind);
    }

    /**
     * @param array $record
     *
     * @return int
     */
    public static function insert($record)
    {
        $sample = static::sample();
        if ($fields = array_diff(array_keys($record), $sample->_di->modelsMetadata->getAttributes($sample))) {
            $sample->_di->logger->debug(['insert `:1` table skip fields: :2', $sample->getSource($record), array_values($fields)]);

            foreach ($fields as $field) {
                unset($record[$field]);
            }
        }

        $sample->getConnection($record)->insert($sample->getSource($record), $record);

        return 1;
    }

    /**
     * @param int|string|array $filters =get_object_vars(new static)
     *
     * @return \ManaPHP\Db\Query|\ManaPHP\QueryInterface
     */
    public static function where($filters)
    {
        return static::select()->where(is_scalar($filters) ? [static::sample()->getPrimaryKey() => $filters] : $filters);
    }

    /**
     * @param array $filters =get_object_vars(new static)
     *
     * @return \ManaPHP\Db\Query|\ManaPHP\QueryInterface
     */
    public static function search($filters)
    {
        return static::select()->search($filters);
    }
}