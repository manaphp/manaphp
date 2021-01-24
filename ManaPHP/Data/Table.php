<?php

namespace ManaPHP\Data;

use ManaPHP\Di;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\Sharding;
use ManaPHP\Helper\Sharding\ShardingTooManyException;
use ManaPHP\Helper\Str;

/**
 * @property-read \ManaPHP\Di $_di
 */
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
     * @param array|\ManaPHP\Data\Model $context =model_var(new static)
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
     * @param array|\ManaPHP\Data\Model $context =model_var(new static)
     *
     * @return array
     */
    public function getMultipleShards($context)
    {
        $db = $this->db();
        $table = $this->table();

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
        $db = $this->db();
        $table = $this->table();

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
    public function table()
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

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getShared($name)
    {
        return $this->_di->getShared($name);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if ($name === '_di') {
            return $this->_di = Di::getDefault();
        } else {
            return null;
        }
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return void
     */
    public function __set($name, $value)
    {
        if (is_scalar($value)) {
            throw new MisuseException(['`%s` Table does\'t contains `%s` field', static::class, $name]);
        }

        $this->$name = $value;
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return false;
    }
}