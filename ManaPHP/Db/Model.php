<?php

namespace ManaPHP\Db;

use ManaPHP\Di;

/**
 * Class ManaPHP\Db\Model
 *
 * @package model
 *
 * @method void initialize()
 * @method void onConstruct()
 *
 * method beforeCreate()
 * method afterCreate()
 *
 * method beforeSave()
 * method afterSave()
 *
 * method afterFetch()
 *
 * method beforeUpdate()
 * method afterUpdate()
 *
 * method beforeDelete()
 * method afterDelete()
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
    public static function getDb($context = null)
    {
        return 'db';
    }

    /**
     * @param mixed $context
     *
     * @return \ManaPHP\DbInterface|false
     */
    public static function getConnection($context = null)
    {
        $db = static::getDb($context);
        if ($db === false) {
            return false;
        }

        return Di::getDefault()->getShared($db);
    }

    /**
     * @return string
     */
    public static function getPrimaryKey()
    {
        return Di::getDefault()->modelsMetadata->getPrimaryKeyAttributes(get_called_class())[0];
    }

    /**
     * @return array
     */
    public static function getFields()
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
    public static function getIntTypeFields()
    {
        return Di::getDefault()->modelsMetadata->getIntTypeAttributes(get_called_class());
    }

    /**
     * @return string
     */
    public static function getAutoIncrementField()
    {
        return Di::getDefault()->modelsMetadata->getAutoIncrementAttribute(get_called_class());
    }

    /**
     * @param string|array $fields
     *
     * @return \ManaPHP\Db\Model\CriteriaInterface
     */
    public static function criteria($fields = null)
    {
        return Di::getDefault()->get('ManaPHP\Db\Model\Criteria', [get_called_class(), $fields]);
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
        $autoIncrementAttribute = static::getAutoIncrementField();
        if ($autoIncrementAttribute !== null) {
            /**
             * @var \ManaPHP\DbInterface $connection
             */
            $this->{$autoIncrementAttribute} = $connection->lastInsertId();
        }
    }
}