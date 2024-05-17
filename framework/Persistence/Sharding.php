<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\Sharding\ShardingTooManyException;
use function count;
use function strlen;

class Sharding implements ShardingInterface
{
    #[Autowired] protected EntityMetadataInterface $entityMetadata;

    public function getAnyShard(string $entityClass): array
    {
        $shards = $this->getAllShards($entityClass);

        return [key($shards), current($shards)[0]];
    }

    public function getUniqueShard(string $entityClass, array|Entity $context): array
    {
        $shards = $this->getMultipleShards($entityClass, $context);
        if (count($shards) !== 1) {
            throw new ShardingTooManyException(['too many dbs: `{dbs}`', 'dbs' => array_keys($shards)]);
        }

        $tables = current($shards);
        if (count($tables) !== 1) {
            throw new ShardingTooManyException(['too many tables: `{tables}`', 'tables' => $tables]);
        }

        return [key($shards), $tables[0]];
    }

    public function getMultipleShards(string $entityClass, array|Entity $context): array
    {
        $connection = $this->entityMetadata->getConnection($entityClass);
        $table = $this->entityMetadata->getTable($entityClass);

        if (strcspn($connection, ':,') === strlen($connection) && strcspn($table, ':,') === strlen($table)) {
            return [$connection => [$table]];
        } else {
            return \ManaPHP\Helper\Sharding::multiple($connection, $table, $context);
        }
    }

    public function getAllShards(string $entityClass): array
    {
        $connection = $this->entityMetadata->getConnection($entityClass);
        $table = $this->entityMetadata->getTable($entityClass);

        if (strcspn($connection, ':,') === strlen($connection) && strcspn($table, ':,') === strlen($table)) {
            return [$connection => [$table]];
        } else {
            return \ManaPHP\Helper\Sharding::all($connection, $table);
        }
    }
}