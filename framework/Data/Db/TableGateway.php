<?php
declare(strict_types=1);

namespace ManaPHP\Data\Db;

use ManaPHP\Component;

/**
 * @property-read \ManaPHP\Data\Db\FactoryInterface     $dbFactory
 * @property-read \ManaPHP\Data\Model\ShardingInterface $sharding
 */
class TableGateway extends Component implements TableGatewayInterface
{
    public function insert(string $model, array $record, bool $fetchInsertId = false): mixed
    {
        list($connection, $table) = $this->sharding->getUniqueShard($model, $record);

        return $this->dbFactory->get($connection)->insert($table, $record, $fetchInsertId);
    }

    public function insertBySql(string $model, string $sql, array $bind = []): int
    {
        list($connection, $table) = $this->sharding->getUniqueShard($model, $bind);

        return $this->dbFactory->get($connection)->insertBySql($table, $sql, $bind);
    }

    public function delete(string $model, string|array $conditions, array $bind = []): int
    {
        $shards = $this->sharding->getMultipleShards($model, $bind);

        $affected_count = 0;
        foreach ($shards as $connection => $tables) {
            $db = $this->dbFactory->get($connection);

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
            $db = $this->dbFactory->get($connection);

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
            $db = $this->dbFactory->get($connection);

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
            $db = $this->dbFactory->get($connection);

            foreach ($tables as $table) {
                $affected_count += $db->updateBySql($table, $sql, $bind);
            }
        }

        return $affected_count;
    }
}