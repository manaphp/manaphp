<?php
declare(strict_types=1);

namespace ManaPHP\Data;

use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\Sharding;
use ManaPHP\Helper\Sharding\ShardingTooManyException;
use ManaPHP\Helper\Str;

/**
 * @property-read \ManaPHP\Di\ContainerInterface $_container
 */
abstract class AbstractTable implements TableInterface
{
    public function getAnyShard(): array
    {
        $shards = $this->getAllShards();

        return [key($shards), current($shards)[0]];
    }

    /**
     * @param array|\ManaPHP\Data\ModelInterface $context =model_var(new static)
     *
     * @return array
     */
    public function getUniqueShard(array|ModelInterface $context): array
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
     * @param array|\ManaPHP\Data\ModelInterface $context =model_var(new static)
     *
     * @return array
     */
    public function getMultipleShards(array|ModelInterface $context): array
    {
        $connection = $this->connection();
        $table = $this->table();

        if (strcspn($connection, ':,') === strlen($connection) && strcspn($table, ':,') === strlen($table)) {
            return [$connection => [$table]];
        } else {
            return Sharding::multiple($connection, $table, $context);
        }
    }

    public function getAllShards(): array
    {
        $connection = $this->connection();
        $table = $this->table();

        if (strcspn($connection, ':,') === strlen($connection) && strcspn($table, ':,') === strlen($table)) {
            return [$connection => [$table]];
        } else {
            return Sharding::all($connection, $table);
        }
    }

    public function table(): string
    {
        $class = static::class;
        return Str::snakelize(($pos = strrpos($class, '\\')) === false ? $class : substr($class, $pos + 1));
    }

    public static function sample(): static
    {
        static $cached;

        $class = static::class;

        if (!isset($cached[$class])) {
            $cached[$class] = new $class;
        }

        return $cached[$class];
    }

    public function getShared(string $name): mixed
    {
        return $this->_container->get($name);
    }

    public function __get(string $name): mixed
    {
        if ($name === '_container') {
            return $this->_container = container();
        } else {
            return null;
        }
    }

    public function __set(string $name, mixed $value): void
    {
        if (is_scalar($value)) {
            throw new MisuseException(['`%s` Table does\'t contains `%s` field', static::class, $name]);
        }

        $this->$name = $value;
    }

    public function __isset(string $name): bool
    {
        return false;
    }
}