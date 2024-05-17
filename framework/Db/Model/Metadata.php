<?php
declare(strict_types=1);

namespace ManaPHP\Db\Model;

use ManaPHP\Db\Db;
use ManaPHP\Db\DbConnectorInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Persistence\ShardingInterface;
use function function_exists;

class Metadata implements MetadataInterface
{
    #[Autowired] protected DbConnectorInterface $connector;
    #[Autowired] protected ShardingInterface $sharding;

    #[Autowired] protected int $ttl = 3600;

    #[Config] protected bool $app_debug;

    /** @noinspection PhpTypedPropertyMightBeUninitializedInspection */
    public function __construct()
    {
        if ($this->app_debug || !function_exists('apcu_fetch')) {
            $this->ttl = 0;
        }
    }

    protected function getMetadata(string $entityClass): array
    {
        $key = __FILE__ . ':' . $entityClass;

        if ($this->ttl > 0) {
            $r = apcu_fetch($key, $success);
            if ($success) {
                return $r;
            }
        }

        list($connection, $table) = $this->sharding->getAnyShard($entityClass);
        $db = $this->connector->get($connection);
        $data = $db->getMetadata($table);

        if ($this->ttl > 0) {
            apcu_store($key, $data);
        }

        return $data;
    }

    public function getAttributes(string $entityClass): array
    {
        return $this->getMetadata($entityClass)[Db::METADATA_ATTRIBUTES];
    }

    public function getPrimaryKeyAttributes(string $entityClass): array
    {
        return $this->getMetadata($entityClass)[Db::METADATA_PRIMARY_KEY];
    }

    public function getAutoIncrementAttribute(string $entityClass): ?string
    {
        return $this->getMetadata($entityClass)[Db::METADATA_AUTO_INCREMENT_KEY];
    }

    public function getIntTypeAttributes(string $entityClass): array
    {
        return $this->getMetadata($entityClass)[Db::METADATA_INT_TYPE_ATTRIBUTES];
    }
}