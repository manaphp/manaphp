<?php

namespace ManaPHP\Db;

use ManaPHP\Di;

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
     * @return string|false
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
        $db = $this->getDb($context);
        return Di::getDefault()->getShared($db);
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return Di::getDefault()->modelsMetadata->getPrimaryKeyAttributes(get_called_class())[0];
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
            $attributes = Di::getDefault()->modelsMetadata->getAttributes($className);
            $intersect = array_intersect($properties, $attributes);

            $fields[$className] = $intersect ?: $attributes;
        }

        return $fields[$className];
    }

    /**
     * @return array|null
     */
    public function getIntTypeFields()
    {
        return Di::getDefault()->modelsMetadata->getIntTypeAttributes(get_called_class());
    }

    /**
     * @return string|null
     */
    public function getAutoIncrementField()
    {
        return Di::getDefault()->modelsMetadata->getAutoIncrementAttribute(get_called_class());
    }

    /**
     * @param string|array      $fields
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

    protected function _postCreate($connection)
    {
        $autoIncrementField = $this->getAutoIncrementField();
        if ($autoIncrementField !== null) {
            /**
             * @var \ManaPHP\DbInterface $connection
             */
            $this->{$autoIncrementField} = $connection->lastInsertId();
        }
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
        return $model->getConnection($bind)->getMasterConnection()->execute("INSERT INTO [$table] " . $sql, $bind);
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
        return $model->getConnection($bind)->getMasterConnection()->execute("DELETE FROM [$table] WHERE " . $sql, $bind);
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
        /** @noinspection SqlNoDataSourceInspection */
        return $model->getConnection($bind)->getMasterConnection()->execute("UPDATE [$table] SET " . $sql, $bind);
    }
}