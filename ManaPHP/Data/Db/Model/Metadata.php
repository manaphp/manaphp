<?php

namespace ManaPHP\Data\Db\Model;

use ManaPHP\Component;
use ManaPHP\Data\Db;

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
     * @param string|\ManaPHP\Data\Db\ModelInterface $model
     *
     * @return array
     */
    protected function _readMetaData($model)
    {
        $modelName = is_string($model) ? $model : get_class($model);

        if (!isset($this->_metadata[$modelName])) {
            $data = $this->read($modelName);
            if ($data !== false) {
                $this->_metadata[$modelName] = $data;
            } else {
                $modelInstance = is_string($model) ? $this->getShared($model) : $model;

                list($db, $table) = $modelInstance->getAnyShard();
                /** @var \ManaPHP\Data\DbInterface $db */
                $db = $this->getShared($db);
                $data = $db->getMetadata($table);

                $this->_metadata[$modelName] = $data;
                $this->write($modelName, $data);
            }
        }

        return $this->_metadata[$modelName];
    }

    /**
     * Returns table attributes names (fields)
     *
     * @param string|\ManaPHP\Data\Db\ModelInterface $model
     *
     * @return array
     */
    public function getAttributes($model)
    {
        return $this->_readMetaData($model)[Db::METADATA_ATTRIBUTES];
    }

    /**
     * Returns an array of fields which are part of the primary key
     *
     * @param string|\ManaPHP\Data\Db\ModelInterface $model
     *
     * @return array
     */
    public function getPrimaryKeyAttributes($model)
    {
        return $this->_readMetaData($model)[Db::METADATA_PRIMARY_KEY];
    }

    /**
     * Returns attribute which is auto increment or null
     *
     * @param string|\ManaPHP\Data\Db\ModelInterface $model
     *
     * @return string |null
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