<?php
declare(strict_types=1);

namespace ManaPHP\Data\Db;

use ManaPHP\Data\AbstractTable;
use ManaPHP\Data\DbInterface;

class Table extends AbstractTable
{
    public function connection(): string
    {
        return 'default';
    }

    public function getDb(string $connection): DbInterface
    {
        return $this->getShared(FactoryInterface::class)->get($connection);
    }

    public static function insert(array $record, bool $fetchInsertId = false): mixed
    {
        /** @noinspection OneTimeUseVariablesInspection */
        $sample = static::sample();

        list($connection, $table) = $sample->getUniqueShard($record);

        $db = static::sample()->getDb($connection);

        return $db->insert($table, $record, $fetchInsertId);
    }

    public static function insertBySql(string $sql, array $bind = []): int
    {
        /** @noinspection OneTimeUseVariablesInspection */
        $sample = static::sample();

        list($connection, $table) = $sample->getUniqueShard($bind);

        $db = static::sample()->getDb($connection);

        return $db->insertBySql($table, $sql, $bind);
    }

    public static function delete(string|array $conditions, array $bind = []): int
    {
        $shards = static::sample()->getMultipleShards($bind);

        $affected_count = 0;
        foreach ($shards as $connection => $tables) {
            $db = static::sample()->getDb($connection);

            foreach ($tables as $table) {
                $affected_count += $db->delete($table, $conditions, $bind);
            }
        }

        return $affected_count;
    }

    public static function deleteBySql(string $sql, array $bind = []): int
    {
        $shards = static::sample()->getMultipleShards($bind);

        $affected_count = 0;
        foreach ($shards as $connection => $tables) {
            $db = static::sample()->getDb($connection);

            foreach ($tables as $table) {
                $affected_count += $db->deleteBySql($table, $sql, $bind);
            }
        }

        return $affected_count;
    }

    public static function update(array $fieldValues, string|array $conditions, array $bind = []): int
    {
        $shards = static::sample()->getMultipleShards($bind);

        $affected_count = 0;
        foreach ($shards as $connection => $tables) {
            $db = static::sample()->getDb($connection);

            foreach ($tables as $table) {
                $affected_count += $db->update($table, $fieldValues, $conditions, $bind);
            }
        }

        return $affected_count;
    }

    public static function updateBySql(string $sql, array $bind = []): int
    {
        $shards = static::sample()->getMultipleShards($bind);

        $affected_count = 0;
        foreach ($shards as $connection => $tables) {
            $db = static::sample()->getDb($connection);

            foreach ($tables as $table) {
                $affected_count += $db->updateBySql($table, $sql, $bind);
            }
        }

        return $affected_count;
    }
}