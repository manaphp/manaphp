<?php

namespace ManaPHP\Db\Model;

/**
 * Interface ManaPHP\Mvc\Model\MetadataInterface
 *
 * @package modelsMetadata
 */
interface MetadataInterface
{

    /**
     * Returns table attributes names (fields)
     *
     * @param string|\ManaPHP\Db\ModelInterface $model
     *
     * @return    array
     */
    public function getAttributes($model);

    /**
     * Returns an array of fields which are part of the primary key
     *
     * @param string|\ManaPHP\Db\ModelInterface $model
     *
     * @return array
     */
    public function getPrimaryKeyAttributes($model);

    /**
     * Returns an array of fields which are not part of the primary key
     *
     * @param string|\ManaPHP\Db\ModelInterface $model
     *
     * @return    array
     */
    public function getNonPrimaryKeyAttributes($model);

    /**
     * Returns attribute which is auto increment or null
     *
     * @param string|\ManaPHP\Db\ModelInterface $model
     *
     * @return string |null
     */
    public function getAutoIncrementAttribute($model);

    /**
     * Check if a model has certain attribute
     *
     * @param string|\ManaPHP\Db\ModelInterface $model
     * @param string                             $attribute
     *
     * @return bool
     */
    public function hasAttribute($model, $attribute);

    /**
     * @param string|\ManaPHP\Db\ModelInterface $model
     *
     * @return array
     */
    public function getColumnProperties($model);
}
