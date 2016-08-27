<?php

namespace ManaPHP\Mvc\Model;

use ManaPHP\Component;

/**
 * ManaPHP\Mvc\Model\MetaData
 *
 * <p>Because ManaPHP\Mvc\Model requires meta-data like field names, data types, primary keys, etc.
 * this component collect them and store for further querying by ManaPHP\Mvc\Model.
 * ManaPHP\Mvc\Model\MetaData can also use adapters to store temporarily or permanently the meta-data.</p>
 *
 * <p>A standard ManaPHP\Mvc\Model\MetaData can be used to query model attributes:</p>
 *
 * <code>
 *    $metaData = new ManaPHP\Mvc\Model\MetaData\Memory();
 *    $attributes = $metaData->getAttributes(new Robots());
 *    print_r($attributes);
 * </code>
 *
 */
abstract class MetaData extends Component implements MetaDataInterface, MetaData\AdapterInterface
{
    const MODEL_ATTRIBUTES = 0;
    const MODEL_PRIMARY_KEY = 1;
    const MODEL_NON_PRIMARY_KEY = 2;
    const MODEL_IDENTITY_COLUMN = 3;
    const MODEL_COLUMN_PROPERTIES = 4;

    /**
     * @var array
     */
    protected $_metaData;

    /**
     * Reads the complete meta-data for certain model
     *
     *<code>
     *    print_r($metaData->readMetaData(new Robots()));
     *</code>
     *
     * @param string|\ManaPHP\Mvc\ModelInterface $model
     *
     * @return array
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    protected function _readMetaData($model)
    {
        $modelName = is_string($model) ? $model : get_class($model);

        if (!isset($this->_metaData[$modelName])) {
            $data = $this->read($modelName);
            if ($data !== false) {
                $this->_metaData[$modelName] = $data;
            } else {
                if (is_string($model)) {
                    $model = new $model();
                }

                /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                $data = $model->getReadConnection()->getMetadata($model->getSource());

                $properties = [];
                foreach ((new \ReflectionClass($model))->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
                    if (!$property->isStatic()) {
                        $properties[] = $property->getName();
                    }
                }

                $diff = array_diff($properties, $data[self::MODEL_ATTRIBUTES]);

                if (count($diff) !== 0) {
                    throw new Exception($modelName . 'is not contains these columns: ' . implode(',', $diff));
                }

                $data[self::MODEL_COLUMN_PROPERTIES] = $properties;

                $this->_metaData[$modelName] = $data;
                $this->write($modelName, $data);
            }
        }

        return $this->_metaData[$modelName];
    }

    /**
     * Returns table attributes names (fields)
     *
     *<code>
     *    print_r($metaData->getAttributes(new Robots()));
     *</code>
     *
     * @param string|\ManaPHP\Mvc\ModelInterface $model
     *
     * @return array
     * @throws \ManaPHP\Mvc\Model\Exception
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
     * @param string|\ManaPHP\Mvc\ModelInterface $model
     *
     * @return array
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function getPrimaryKeyAttributes($model)
    {
        return $this->_readMetaData($model)[self::MODEL_PRIMARY_KEY];
    }

    /**
     * Returns attribute which is auto increment or null
     *
     * @param string|\ManaPHP\Mvc\ModelInterface $model
     *
     * @return string |null
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function getAutoIncrementAttribute($model)
    {
        return $this->_readMetaData($model)[self::MODEL_IDENTITY_COLUMN];
    }

    /**
     * Returns an array of fields which are not part of the primary key
     *
     * @param string|\ManaPHP\Mvc\ModelInterface $model
     *
     * @return    array
     * @throws \ManaPHP\Mvc\Model\Exception
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
     * @param string|\ManaPHP\Mvc\ModelInterface $model
     * @param string                             $attribute
     *
     * @return boolean
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function hasAttribute($model, $attribute)
    {
        return isset($this->_readMetaData($model)[self::MODEL_ATTRIBUTES][$attribute]);
    }

    /**
     * @param string|\ManaPHP\Mvc\ModelInterface $model
     *
     * @return array
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function getColumnProperties($model)
    {
        return $this->_readMetaData($model)[self::MODEL_COLUMN_PROPERTIES];
    }
}