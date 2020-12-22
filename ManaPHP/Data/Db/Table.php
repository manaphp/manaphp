<?php

namespace ManaPHP\Data\Db;

class Table extends \ManaPHP\Data\Table
{
    /**
     * @return string
     */
    public function getDb()
    {
        return 'db';
    }

    /**
     * @param mixed $context =model_var(new static)
     *
     * @return \ManaPHP\Data\DbInterface
     */
    public static function connection($context = null)
    {
        list($db) = static::sample()->getUniqueShard($context);

        return static::sample()->getShared($db);
    }

    /**
     * @param array $record
     * @param bool  $fetchInsertId
     *
     * @return int|string|null
     * @throws \ManaPHP\Data\Db\Exception
     */
    public static function insert($record, $fetchInsertId = false)
    {
        $sample = static::sample();

        list($db, $table) = $sample->getUniqueShard($record);

        /** @var \ManaPHP\Data\DbInterface $db */
        $db = static::sample()->getShared($db);

        return $db->insert($table, $record, $fetchInsertId);
    }

    /**
     * @param string $sql
     * @param array  $bind
     *
     * @return int
     */
    public static function insertBySql($sql, $bind = [])
    {
        $sample = static::sample();

        list($db, $table) = $sample->getUniqueShard($bind);

        /** @var \ManaPHP\Data\DbInterface $db */
        $db = static::sample()->getShared($db);

        return $db->insertBySql($table, $sql, $bind);
    }

    /**
     * Deletes data from a table using custom SQL syntax
     *
     * @param string|array $conditions
     * @param array        $bind
     *
     * @return int
     */
    public static function delete($conditions, $bind = [])
    {
        $shards = static::sample()->getMultipleShards($bind);

        $affected_count = 0;
        foreach ($shards as $db => $tables) {
            /** @var \ManaPHP\Data\DbInterface $db */
            $db = static::sample()->getShared($db);

            foreach ($tables as $table) {
                $affected_count += $db->delete($table, $conditions, $bind);
            }
        }

        return $affected_count;
    }

    /**
     * @param string $sql
     * @param array  $bind
     *
     * @return int
     */
    public static function deleteBySql($sql, $bind = [])
    {
        $shards = static::sample()->getMultipleShards($bind);

        $affected_count = 0;
        foreach ($shards as $db => $tables) {
            /** @var \ManaPHP\Data\DbInterface $db */
            $db = static::sample()->getShared($db);

            foreach ($tables as $table) {
                $affected_count += $db->deleteBySql($table, $sql, $bind);
            }
        }

        return $affected_count;
    }

    /**
     * Updates data on a table using custom SQL syntax
     *
     * @param array        $fieldValues
     * @param string|array $conditions
     * @param array        $bind
     *
     * @return    int
     */
    public static function update($fieldValues, $conditions, $bind = [])
    {
        $shards = static::sample()->getMultipleShards($bind);

        $affected_count = 0;
        foreach ($shards as $db => $tables) {
            /** @var \ManaPHP\Data\DbInterface $db */
            $db = static::sample()->getShared($db);

            foreach ($tables as $table) {
                $affected_count += $db->update($table, $fieldValues, $conditions, $bind);
            }
        }

        return $affected_count;
    }

    /**
     * @param string $sql
     * @param array  $bind
     *
     * @return int
     */
    public static function updateBySql($sql, $bind = [])
    {
        $shards = static::sample()->getMultipleShards($bind);

        $affected_count = 0;
        foreach ($shards as $db => $tables) {
            /** @var \ManaPHP\Data\DbInterface $db */
            $db = static::sample()->getShared($db);

            foreach ($tables as $table) {
                $affected_count += $db->updateBySql($table, $sql, $bind);
            }
        }

        return $affected_count;
    }
}