<?php

namespace ManaPHP\Db;

use ManaPHP\Db\Model\Exception as ModelException;
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
     * @throws \ManaPHP\Db\Model\Exception
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
     * @return array
     */
    public static function getPrimaryKey()
    {
        return Di::getDefault()->modelsMetadata->getPrimaryKeyAttributes(get_called_class());
    }

    /**
     * @return array
     */
    public static function getFields()
    {
        return Di::getDefault()->modelsMetadata->getAttributes(get_called_class());
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
    public static function createCriteria($fields = null)
    {
        return Di::getDefault()->get('ManaPHP\Db\Model\Criteria', [get_called_class(), $fields]);
    }

    /**
     * alias of createQuery
     *
     * @param string $alias
     *
     * @return \ManaPHP\Db\Model\QueryInterface
     */
    public static function query($alias = null)
    {
        return static::createQuery($alias);
    }

    /**
     * Create a criteria for a specific model
     *
     * @param string $alias
     *
     * @return \ManaPHP\Db\Model\QueryInterface
     */
    public static function createQuery($alias = null)
    {
        return Di::getDefault()->get('ManaPHP\Db\Model\Query')->from(get_called_class(), $alias);
    }

    /**
     * Inserts a model instance. If the instance already exists in the persistence it will throw an exception
     * Returning true on success or false otherwise.
     *
     *<code>
     *    //Creating a new robot
     *    $robot = new Robots();
     *    $robot->type = 'mechanical';
     *    $robot->name = 'Boy';
     *    $robot->year = 1952;
     *    $robot->create();
     *
     *  //Passing an array to create
     *  $robot = new Robots();
     *  $robot->create(array(
     *      'type' => 'mechanical',
     *      'name' => 'Boy',
     *      'year' => 1952
     *  ));
     *</code>
     *
     * @return void
     * @throws \ManaPHP\Db\Model\Exception
     */
    public function create()
    {
        $fieldValues = [];
        foreach (static::getFields() as $field) {
            if ($this->{$field} !== null) {
                $fieldValues[$field] = $this->{$field};
            }
        }

        if (($db = static::getDb($this)) === false) {
            throw new ModelException('`:model` model db sharding for insert failed',
                ['model' => get_called_class(), 'context' => $this]);
        }

        if (($source = static::getSource($this)) === false) {
            throw new ModelException('`:model` model table sharding for insert failed',
                ['model' => get_called_class(), 'context' => $this]);
        }

        if ($this->_fireEventCancel('beforeSave') === false || $this->_fireEventCancel('beforeCreate') === false) {
            throw new ModelException('`:model` model cannot be created because it has been cancel.'/**m092e54c70ff7ecc1a*/, ['model' => get_class($this)]);
        }

        $connection = $this->_dependencyInjector->getShared($db);
        $connection->insert($source, $fieldValues);

        $autoIncrementAttribute = static::getAutoIncrementField();
        if ($autoIncrementAttribute !== null) {
            $this->{$autoIncrementAttribute} = $connection->lastInsertId();
        }

        $this->_snapshot = $this->toArray();

        $this->_fireEvent('afterCreate');
        $this->_fireEvent('afterSave');
    }
}