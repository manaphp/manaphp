<?php

namespace ManaPHP\Data\Db\Model;

use ManaPHP\Component;
use ManaPHP\Data\Db;

/**
 * @property-read \ManaPHP\ConfigInterface $config
 */
class Metadata extends Component implements MetadataInterface
{
    /**
     * @var int
     */
    protected $ttl = 3600;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['ttl'])) {
            $this->ttl = (int)$options['ttl'];
        }

        if ($this->config->get('debug') || !function_exists('apcu_fetch')) {
            $this->ttl = 0;
        }
    }

    /**
     * Reads the complete meta-data for certain model
     *
     * @param string|\ManaPHP\Data\Db\ModelInterface $model
     *
     * @return array
     */
    protected function getMetadata($model)
    {
        $modelName = is_string($model) ? $model : get_class($model);
        $key = __FILE__ . ':' . $modelName;

        if ($this->ttl > 0) {
            $r = apcu_fetch($key, $success);
            if ($success) {
                return $r;
            }
        }

        $modelInstance = is_string($model) ? $this->injector->get($model) : $model;

        list($db, $table) = $modelInstance->getAnyShard();
        /** @var \ManaPHP\Data\DbInterface $db */
        $db = $this->injector->get($db);
        $data = $db->getMetadata($table);

        if ($this->ttl > 0) {
            apcu_store($key, $data);
        }

        return $data;
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
        return $this->getMetadata($model)[Db::METADATA_ATTRIBUTES];
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
        return $this->getMetadata($model)[Db::METADATA_PRIMARY_KEY];
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
        return $this->getMetadata($model)[Db::METADATA_AUTO_INCREMENT_KEY];
    }

    /**
     * @param string $model
     *
     * @return array
     */
    public function getIntTypeAttributes($model)
    {
        return $this->getMetadata($model)[Db::METADATA_INT_TYPE_ATTRIBUTES];
    }
}