<?php
declare(strict_types=1);

namespace ManaPHP\Data\Db;

use ManaPHP\Data\Model\ThoseInterface;
use ManaPHP\Helper\Container;

class Table
{
    public function connection(): string
    {
        return 'default';
    }

    public static function insert(array $record, bool $fetchInsertId = false): mixed
    {
        /** @noinspection OneTimeUseVariablesInspection */
        $that = Container::get(ThoseInterface::class)->get(static::class);

        list($connection, $table) = $that->getUniqueShard($record);

        $db = Container::get(FactoryInterface::class)->get($connection);

        return $db->insert($table, $record, $fetchInsertId);
    }

    public static function insertBySql(string $sql, array $bind = []): int
    {
        /** @noinspection OneTimeUseVariablesInspection */
        $that = Container::get(ThoseInterface::class)->get(static::class);

        list($connection, $table) = $that->getUniqueShard($bind);

        $db = Container::get(FactoryInterface::class)->get($connection);

        return $db->insertBySql($table, $sql, $bind);
    }

    public static function delete(string|array $conditions, array $bind = []): int
    {
        $shards = Container::get(ThoseInterface::class)->get(static::class)->getMultipleShards($bind);

        $affected_count = 0;
        foreach ($shards as $connection => $tables) {
            $db = Container::get(FactoryInterface::class)->get($connection);

            foreach ($tables as $table) {
                $affected_count += $db->delete($table, $conditions, $bind);
            }
        }

        return $affected_count;
    }

    public static function deleteBySql(string $sql, array $bind = []): int
    {
        $shards = Container::get(ThoseInterface::class)->get(static::class)->getMultipleShards($bind);

        $affected_count = 0;
        foreach ($shards as $connection => $tables) {
            $db = Container::get(FactoryInterface::class)->get($connection);

            foreach ($tables as $table) {
                $affected_count += $db->deleteBySql($table, $sql, $bind);
            }
        }

        return $affected_count;
    }

    public static function update(array $fieldValues, string|array $conditions, array $bind = []): int
    {
        $shards = Container::get(ThoseInterface::class)->get(static::class)->getMultipleShards($bind);

        $affected_count = 0;
        foreach ($shards as $connection => $tables) {
            $db = Container::get(FactoryInterface::class)->get($connection);

            foreach ($tables as $table) {
                $affected_count += $db->update($table, $fieldValues, $conditions, $bind);
            }
        }

        return $affected_count;
    }

    public static function updateBySql(string $sql, array $bind = []): int
    {
        $shards = Container::get(ThoseInterface::class)->get(static::class)->getMultipleShards($bind);

        $affected_count = 0;
        foreach ($shards as $connection => $tables) {
            $db = Container::get(FactoryInterface::class)->get($connection);

            foreach ($tables as $table) {
                $affected_count += $db->updateBySql($table, $sql, $bind);
            }
        }

        return $affected_count;
    }
}