<?php
declare(strict_types=1);

namespace ManaPHP\Data\Db\Model;

use ManaPHP\Component;
use ManaPHP\Data\Db;

/**
 * @property-read \ManaPHP\ConfigInterface              $config
 * @property-read \ManaPHP\Data\Db\FactoryInterface     $dbFactory
 * @property-read \ManaPHP\Data\Model\ShardingInterface $sharding
 */
class Metadata extends Component implements MetadataInterface
{
    protected int $ttl;

    public function __construct(int $ttl = 3600)
    {
        $this->ttl = $ttl;

        /** @noinspection NotOptimalIfConditionsInspection */
        if ($this->config->get('debug') || !function_exists('apcu_fetch')) {
            $this->ttl = 0;
        }
    }

    protected function getMetadata(string $model): array
    {
        $key = __FILE__ . ':' . $model;

        if ($this->ttl > 0) {
            $r = apcu_fetch($key, $success);
            if ($success) {
                return $r;
            }
        }

        list($connection, $table) = $this->sharding->getAnyShard($model);
        $db = $this->dbFactory->get($connection);
        $data = $db->getMetadata($table);

        if ($this->ttl > 0) {
            apcu_store($key, $data);
        }

        return $data;
    }

    public function getAttributes(string $model): array
    {
        return $this->getMetadata($model)[Db::METADATA_ATTRIBUTES];
    }

    public function getPrimaryKeyAttributes(string $model): array
    {
        return $this->getMetadata($model)[Db::METADATA_PRIMARY_KEY];
    }

    public function getAutoIncrementAttribute(string $model): ?string
    {
        return $this->getMetadata($model)[Db::METADATA_AUTO_INCREMENT_KEY];
    }

    public function getIntTypeAttributes(string $model): array
    {
        return $this->getMetadata($model)[Db::METADATA_INT_TYPE_ATTRIBUTES];
    }
}