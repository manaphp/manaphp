<?php
declare(strict_types=1);

namespace ManaPHP\Data\Db;

use ManaPHP\Component;

/**
 * @property-read \ManaPHP\Data\Model\ThoseInterface $those
 * @property-read \ManaPHP\Data\Db\FactoryInterface  $dbFactory
 */
class TableGateway extends Component implements TableGatewayInterface
{
    public function insert(string $model, array $record, bool $fetchInsertId = false): mixed
    {
        /** @noinspection OneTimeUseVariablesInspection */
        $that = $this->those->get($model);

        list($connection, $table) = $that->getUniqueShard($record);

        $db = $this->dbFactory->get($connection);

        return $db->insert($table, $record, $fetchInsertId);
    }

    public function insertBySql(string $model, string $sql, array $bind = []): int
    {
        /** @noinspection OneTimeUseVariablesInspection */
        $that = $this->those->get($model);

        list($connection, $table) = $that->getUniqueShard($bind);

        $db = $this->dbFactory->get($connection);

        return $db->insertBySql($table, $sql, $bind);
    }

    public function delete(string $model, string|array $conditions, array $bind = []): int
    {
        $shards = $this->those->get($model)->getMultipleShards($bind);

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
        $shards = $this->those->get($model)->getMultipleShards($bind);

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
        $shards = $this->those->get($model)->getMultipleShards($bind);

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
        $shards = $this->those->get($model)->getMultipleShards($bind);

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