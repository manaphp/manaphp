<?php
declare(strict_types=1);

namespace ManaPHP\Data\Db\Model;

use ManaPHP\Component;
use ManaPHP\Data\Db;
use ManaPHP\Data\Db\ModelInterface;

/**
 * @property-read \ManaPHP\ConfigInterface $config
 */
class Metadata extends Component implements MetadataInterface
{
    protected int $ttl = 3600;

    public function __construct(array $options = [])
    {
        if (isset($options['ttl'])) {
            $this->ttl = (int)$options['ttl'];
        }

        /** @noinspection NotOptimalIfConditionsInspection */
        if ($this->config->get('debug') || !function_exists('apcu_fetch')) {
            $this->ttl = 0;
        }
    }

    protected function getMetadata(string|Db\ModelInterface $model): array
    {
        $modelName = is_string($model) ? $model : get_class($model);
        $key = __FILE__ . ':' . $modelName;

        if ($this->ttl > 0) {
            $r = apcu_fetch($key, $success);
            if ($success) {
                return $r;
            }
        }

        /** @noinspection OneTimeUseVariablesInspection */
        $modelInstance = is_string($model) ? $this->container->get($model) : $model;

        list($db, $table) = $modelInstance->getAnyShard();
        /** @var \ManaPHP\Data\DbInterface $dbInstance */
        $dbInstance = $this->container->get($db);
        $data = $dbInstance->getMetadata($table);

        if ($this->ttl > 0) {
            apcu_store($key, $data);
        }

        return $data;
    }

    public function getAttributes(string|ModelInterface $model): array
    {
        return $this->getMetadata($model)[Db::METADATA_ATTRIBUTES];
    }

    public function getPrimaryKeyAttributes(string|ModelInterface $model): array
    {
        return $this->getMetadata($model)[Db::METADATA_PRIMARY_KEY];
    }

    public function getAutoIncrementAttribute(string|ModelInterface $model): ?string
    {
        return $this->getMetadata($model)[Db::METADATA_AUTO_INCREMENT_KEY];
    }

    public function getIntTypeAttributes(string|ModelInterface $model): array
    {
        return $this->getMetadata($model)[Db::METADATA_INT_TYPE_ATTRIBUTES];
    }
}