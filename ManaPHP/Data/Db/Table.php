<?php
declare(strict_types=1);

namespace ManaPHP\Data\Db;

use ManaPHP\Data\DbInterface;

class Table extends \ManaPHP\Data\AbstractTable
{
    public function db(): string
    {
        return 'db';
    }

    /**
     * @param mixed $context =model_var(new static)
     *
     * @return \ManaPHP\Data\DbInterface
     */
    public static function connection(mixed $context = null): DbInterface
    {
        list($db) = static::sample()->getUniqueShard($context);

        return static::sample()->getShared($db);
    }

    public static function insert(array $record, bool $fetchInsertId = false): mixed
    {
        $sample = static::sample();

        list($db_id, $table) = $sample->getUniqueShard($record);

        /** @var \ManaPHP\Data\DbInterface $db */
        $db = static::sample()->getShared($db_id);

        return $db->insert($table, $record, $fetchInsertId);
    }

    public static function insertBySql(string $sql, array $bind = []): int
    {
        $sample = static::sample();

        list($db, $table) = $sample->getUniqueShard($bind);

        /** @var \ManaPHP\Data\DbInterface $dbInstance */
        $dbInstance = static::sample()->getShared($db);

        return $dbInstance->insertBySql($table, $sql, $bind);
    }

    public static function delete(string|array $conditions, array $bind = []): int
    {
        $shards = static::sample()->getMultipleShards($bind);

        $affected_count = 0;
        foreach ($shards as $db => $tables) {
            /** @var \ManaPHP\Data\DbInterface $dbInstance */
            $dbInstance = static::sample()->getShared($db);

            foreach ($tables as $table) {
                $affected_count += $dbInstance->delete($table, $conditions, $bind);
            }
        }

        return $affected_count;
    }

    public static function deleteBySql(string $sql, array $bind = []): int
    {
        $shards = static::sample()->getMultipleShards($bind);

        $affected_count = 0;
        foreach ($shards as $db => $tables) {
            /** @var \ManaPHP\Data\DbInterface $dbInstance */
            $dbInstance = static::sample()->getShared($db);

            foreach ($tables as $table) {
                $affected_count += $dbInstance->deleteBySql($table, $sql, $bind);
            }
        }

        return $affected_count;
    }

    public static function update(array $fieldValues, string|array $conditions, array $bind = []): int
    {
        $shards = static::sample()->getMultipleShards($bind);

        $affected_count = 0;
        foreach ($shards as $db => $tables) {
            /** @var \ManaPHP\Data\DbInterface $dbInstance */
            $dbInstance = static::sample()->getShared($db);

            foreach ($tables as $table) {
                $affected_count += $dbInstance->update($table, $fieldValues, $conditions, $bind);
            }
        }

        return $affected_count;
    }

    public static function updateBySql(string $sql, array $bind = []): int
    {
        $shards = static::sample()->getMultipleShards($bind);

        $affected_count = 0;
        foreach ($shards as $id => $tables) {
            /** @var \ManaPHP\Data\DbInterface $db */
            $db = static::sample()->getShared($id);

            foreach ($tables as $table) {
                $affected_count += $db->updateBySql($table, $sql, $bind);
            }
        }

        return $affected_count;
    }
}