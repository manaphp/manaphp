<?php

namespace ManaPHP\Db\Model;

use ManaPHP\Component;
use ManaPHP\Db;
use ManaPHP\Db\Model\Metadata\Exception as MetadataException;

/**
 * Class ManaPHP\Mvc\Model\Metadata
 *
 * @package modelsMetadata
 */
abstract class Metadata extends Component implements MetadataInterface, Metadata\AdapterInterface
{
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
                $modelInstance = is_string($model) ? new $model : $model;

                $data = $this->_di->getShared($modelInstance->getDb(true))->getMetadata($modelInstance->getSource(true));

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
        return $this->_readMetaData($model)[Db::METADATA_ATTRIBUTES];
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
        return $this->_readMetaData($model)[Db::METADATA_PRIMARY_KEY];
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
        return $this->_readMetaData($model)[Db::METADATA_AUTO_INCREMENT_KEY];
    }

    /**
     * @param string $model
     *
     * @return array
     */
    public function getIntTypeAttributes($model)
    {
        return $this->_readMetaData($model)[Db::METADATA_INT_TYPE_ATTRIBUTES];
    }
}