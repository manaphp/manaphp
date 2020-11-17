<?php

namespace ManaPHP\Data\Db\Model;

interface MetadataInterface
{

    /**
     * Returns table attributes names (fields)
     *
     * @param string|\ManaPHP\Data\Db\ModelInterface $model
     *
     * @return    array
     */
    public function getAttributes($model);

    /**
     * Returns an array of fields which are part of the primary key
     *
     * @param string|\ManaPHP\Data\Db\ModelInterface $model
     *
     * @return array
     */
    public function getPrimaryKeyAttributes($model);

    /**
     * Returns attribute which is auto increment or null
     *
     * @param string|\ManaPHP\Data\Db\ModelInterface $model
     *
     * @return string |null
     */
    public function getAutoIncrementAttribute($model);

    /**
     * @param string $model
     *
     * @return array
     */
    public function getIntTypeAttributes($model);
}
