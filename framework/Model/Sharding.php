<?php
declare(strict_types=1);

namespace ManaPHP\Model;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Helper\Sharding\ShardingTooManyException;

class Sharding implements ShardingInterface
{
    #[Inject] protected ModelManagerInterface $modelManager;

    public function getAnyShard(string $model): array
    {
        $shards = $this->getAllShards($model);

        return [key($shards), current($shards)[0]];
    }

    public function getUniqueShard(string $model, array|ModelInterface $context): array
    {
        $shards = $this->getMultipleShards($model, $context);
        if (count($shards) !== 1) {
            throw new ShardingTooManyException(['too many dbs: `{dbs}`', 'dbs' => array_keys($shards)]);
        }

        $tables = current($shards);
        if (count($tables) !== 1) {
            throw new ShardingTooManyException(['too many tables: `{tables}`', 'tables' => $tables]);
        }

        return [key($shards), $tables[0]];
    }

    public function getMultipleShards(string $model, array|ModelInterface $context): array
    {
        $connection = $this->modelManager->getConnection($model);
        $table = $this->modelManager->getTable($model);

        if (strcspn($connection, ':,') === strlen($connection) && strcspn($table, ':,') === strlen($table)) {
            return [$connection => [$table]];
        } else {
            return \ManaPHP\Helper\Sharding::multiple($connection, $table, $context);
        }
    }

    public function getAllShards(string $model): array
    {
        $connection = $this->modelManager->getConnection($model);
        $table = $this->modelManager->getTable($model);

        if (strcspn($connection, ':,') === strlen($connection) && strcspn($table, ':,') === strlen($table)) {
            return [$connection => [$table]];
        } else {
            return \ManaPHP\Helper\Sharding::all($connection, $table);
        }
    }
}