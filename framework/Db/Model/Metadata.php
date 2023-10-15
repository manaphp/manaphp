<?php
declare(strict_types=1);

namespace ManaPHP\Db\Model;

use ManaPHP\Db\Db;
use ManaPHP\Db\DbConnectorInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\ConfigInterface;
use ManaPHP\Model\ShardingInterface;

class Metadata implements MetadataInterface
{
    #[Autowired] protected ConfigInterface $config;
    #[Autowired] protected DbConnectorInterface $connector;
    #[Autowired] protected ShardingInterface $sharding;

    #[Autowired] protected int $ttl = 3600;

    /** @noinspection PhpTypedPropertyMightBeUninitializedInspection */
    public function __construct()
    {
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
        $db = $this->connector->get($connection);
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