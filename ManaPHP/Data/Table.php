<?php

namespace ManaPHP\Data;

use ManaPHP\Helper\Sharding;
use ManaPHP\Helper\Sharding\ShardingTooManyException;
use ManaPHP\Helper\Str;

abstract class Table implements TableInterface
{
    /**
     * @return array
     */
    public function getAnyShard()
    {
        $shards = $this->getAllShards();

        return [key($shards), current($shards)[0]];
    }

    /**
     * @param array|\ManaPHP\Data\Model $context =get_object_vars(new static)
     *
     * @return array
     */
    public function getUniqueShard($context)
    {
        $shards = $this->getMultipleShards($context);
        if (count($shards) !== 1) {
            throw new ShardingTooManyException(['too many dbs: `:dbs`', 'dbs' => array_keys($shards)]);
        }

        $tables = current($shards);
        if (count($tables) !== 1) {
            throw new ShardingTooManyException(['too many tables: `:tables`', 'tables' => $tables]);
        }

        return [key($shards), $tables[0]];
    }

    /**
     * @param array|\ManaPHP\Data\Model $context =get_object_vars(new static)
     *
     * @return array
     */
    public function getMultipleShards($context)
    {
        $db = $this->getDb();
        $table = $this->getTable();

        if (strcspn($db, ':,') === strlen($db) && strcspn($table, ':,') === strlen($table)) {
            return [$db => [$table]];
        } else {
            return Sharding::multiple($db, $table, $context);
        }
    }

    /**
     * @return array
     */
    public function getAllShards()
    {
        $db = $this->getDb();
        $table = $this->getTable();

        if (strcspn($db, ':,') === strlen($db) && strcspn($table, ':,') === strlen($table)) {
            return [$db => [$table]];
        } else {
            return Sharding::all($db, $table);
        }
    }

    /**
     * Returns table name mapped in the model
     *
     * @return string
     */
    public function getTable()
    {
        $class = static::class;
        return Str::underscore(($pos = strrpos($class, '\\')) === false ? $class : substr($class, $pos + 1));
    }

    /**
     * @return static
     */
    public static function sample()
    {
        static $cached;

        $class = static::class;

        if (!isset($cached[$class])) {
            $cached[$class] = new $class;
        }

        return $cached[$class];
    }
}