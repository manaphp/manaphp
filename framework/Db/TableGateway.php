<?php
declare(strict_types=1);

namespace ManaPHP\Db;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Model\ShardingInterface;

class TableGateway implements TableGatewayInterface
{
    #[Autowired] protected DbConnectorInterface $connector;
    #[Autowired] protected ShardingInterface $sharding;

    public function insert(string $model, array $record, bool $fetchInsertId = false): mixed
    {
        list($connection, $table) = $this->sharding->getUniqueShard($model, $record);

        return $this->connector->get($connection)->insert($table, $record, $fetchInsertId);
    }

    public function insertBySql(string $model, string $sql, array $bind = []): int
    {
        list($connection, $table) = $this->sharding->getUniqueShard($model, $bind);

        return $this->connector->get($connection)->insertBySql($table, $sql, $bind);
    }

    public function delete(string $model, string|array $conditions, array $bind = []): int
    {
        $shards = $this->sharding->getMultipleShards($model, $bind);

        $affected_count = 0;
        foreach ($shards as $connection => $tables) {
            $db = $this->connector->get($connection);

            foreach ($tables as $table) {
                $affected_count += $db->delete($table, $conditions, $bind);
            }
        }

        return $affected_count;
    }

    public function deleteBySql(string $model, string $sql, array $bind = []): int
    {
        $shards = $this->sharding->getMultipleShards($model, $bind);

        $affected_count = 0;
        foreach ($shards as $connection => $tables) {
            $db = $this->connector->get($connection);

            foreach ($tables as $table) {
                $affected_count += $db->deleteBySql($table, $sql, $bind);
            }
        }

        return $affected_count;
    }

    public function update(string $model, array $fieldValues, string|array $conditions, array $bind = []): int
    {
        $shards = $this->sharding->getMultipleShards($model, $bind);

        $affected_count = 0;
        foreach ($shards as $connection => $tables) {
            $db = $this->connector->get($connection);

            foreach ($tables as $table) {
                $affected_count += $db->update($table, $fieldValues, $conditions, $bind);
            }
        }

        return $affected_count;
    }

    public function updateBySql(string $model, string $sql, array $bind = []): int
    {
        $shards = $this->sharding->getMultipleShards($model, $bind);

        $affected_count = 0;
        foreach ($shards as $connection => $tables) {
            $db = $this->connector->get($connection);

            foreach ($tables as $table) {
                $affected_count += $db->updateBySql($table, $sql, $bind);
            }
        }

        return $affected_count;
    }
}