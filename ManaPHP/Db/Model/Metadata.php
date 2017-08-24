<?php

namespace ManaPHP\Db\Model;

use ManaPHP\Component;
use ManaPHP\Db\Model\Metadata\Exception as MetadataException;

/**
 * Class ManaPHP\Mvc\Model\Metadata
 *
 * @package modelsMetadata
 */
abstract class Metadata extends Component implements MetadataInterface, Metadata\AdapterInterface
{
    const MODEL_ATTRIBUTES = 0;
    const MODEL_PRIMARY_KEY = 1;
    const MODEL_NON_PRIMARY_KEY = 2;
    const MODEL_IDENTITY_COLUMN = 3;
    const MODEL_COLUMN_PROPERTIES = 4;

    /**
     * @var array
     */
    protected $_metadata;

    /**
     * Reads the complete meta-data for certain model
     *
     *<code>
     *    print_r($metaData->readMetaData(new Robots()));
     *</code>
     *
     * @param string|\ManaPHP\Db\ModelInterface $model
     *
     * @return array
     * @throws \ManaPHP\Db\Model\Metadata\Exception
     */
    protected function _readMetaData($model)
    {
        $modelName = is_string($model) ? $model : get_class($model);

        if (!isset($this->_metadata[$modelName])) {
            $data = $this->read($modelName);
            if ($data !== false) {
                $this->_metadata[$modelName] = $data;
            } else {
                if (is_string($model)) {
                    $model = new $model();
                }

                $data = $this->_dependencyInjector->getShared($model->getDb(true))->getMetadata($model->getSource(true));

                $properties = [];
                /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                foreach ((new \ReflectionClass($model))->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
                    if (!$property->isStatic()) {
                        $properties[] = $property->getName();
                    }
                }

                $diff = array_diff($properties, $data[self::MODEL_ATTRIBUTES]);

                if (count($diff) !== 0) {
                    throw new MetadataException('`:table` table of `:model` model is not contains `:columns` columns'/**m0bb273aae32bfd843*/,
                        ['table' => $model->getSource(true), 'model' => $modelName, 'columns' => implode(',', $diff)]);
                }

                $data[self::MODEL_COLUMN_PROPERTIES] = $properties ?: $data[self::MODEL_ATTRIBUTES];

                $this->_metadata[$modelName] = $data;
                $this->write($modelName, $data);
            }
        }

        return $this->_metadata[$modelName];
    }

    /**
     * Returns table attributes names (fields)
     *
     *<code>
     *    print_r($metaData->getAttributes(new Robots()));
     *</code>
     *
     * @param string|\ManaPHP\Db\ModelInterface $model
     *
     * @return array
     * @throws \ManaPHP\Db\Model\Metadata\Exception
     */
    public function getAttributes($model)
    {
        return $this->_readMetaData($model)[self::MODEL_ATTRIBUTES];
    }

    /**
     * Returns an array of fields which are part of the primary key
     *
     *<code>
     *    print_r($metaData->getPrimaryKeyAttributes(new Robots()));
     *</code>
     *
     * @param string|\ManaPHP\Db\ModelInterface $model
     *
     * @return array
     * @throws \ManaPHP\Db\Model\Metadata\Exception
     */
    public function getPrimaryKeyAttributes($model)
    {
        return $this->_readMetaData($model)[self::MODEL_PRIMARY_KEY];
    }

    /**
     * Returns attribute which is auto increment or null
     *
     * @param string|\ManaPHP\Db\ModelInterface $model
     *
     * @return string |null
     * @throws \ManaPHP\Db\Model\Metadata\Exception
     */
    public function getAutoIncrementAttribute($model)
    {
        return $this->_readMetaData($model)[self::MODEL_IDENTITY_COLUMN];
    }

    /**
     * Returns an array of fields which are not part of the primary key
     *
     * @param string|\ManaPHP\Db\ModelInterface $model
     *
     * @return    array
     * @throws \ManaPHP\Db\Model\Metadata\Exception
     */
    public function getNonPrimaryKeyAttributes($model)
    {
        return $this->_readMetaData($model)[self::MODEL_NON_PRIMARY_KEY];
    }

    /**
     * Check if a model has certain attribute
     *
     *<code>
     *    var_dump($metaData->hasAttribute(new Robots(), 'name'));
     *</code>
     *
     * @param string|\ManaPHP\Db\ModelInterface $model
     * @param string                             $attribute
     *
     * @return bool
     * @throws \ManaPHP\Db\Model\Metadata\Exception
     */
    public function hasAttribute($model, $attribute)
    {
        return isset($this->_readMetaData($model)[self::MODEL_ATTRIBUTES][$attribute]);
    }

    /**
     * @param string|\ManaPHP\Db\ModelInterface $model
     *
     * @return array
     * @throws \ManaPHP\Db\Model\Metadata\Exception
     */
    public function getColumnProperties($model)
    {
        return $this->_readMetaData($model)[self::MODEL_COLUMN_PROPERTIES];
    }
}