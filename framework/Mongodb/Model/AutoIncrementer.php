<?php
declare(strict_types=1);

namespace ManaPHP\Mongodb\Model;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Mongodb\MongodbConnectorInterface;
use ManaPHP\Mongodb\MongodbInterface;
use ManaPHP\Persistence\EntityMetadataInterface;
use ManaPHP\Persistence\ShardingInterface;

class AutoIncrementer implements AutoIncrementerInterface
{
    #[Autowired] protected EntityMetadataInterface $entityMetadata;
    #[Autowired] protected ShardingInterface $sharding;
    #[Autowired] protected MongodbConnectorInterface $connector;

    protected function createAutoIncrementIndex(MongodbInterface $mongodb, string $source): bool
    {
        $primaryKey = $this->entityMetadata->getPrimaryKey(static::class);

        if ($pos = strpos($source, '.')) {
            $db = substr($source, 0, $pos);
            $collection = substr($source, $pos + 1);
        } else {
            $db = null;
            $collection = $source;
        }

        $collection = $mongodb->getPrefix() . $collection;

        $command = [
            'createIndexes' => $collection,
            'indexes'       => [
                [
                    'key'    => [
                        $primaryKey => 1
                    ],
                    'unique' => true,
                    'name'   => $primaryKey
                ]
            ]
        ];

        $mongodb->command($command, $db);

        return true;
    }

    public function getNext(string $entityClass, int $step = 1): int
    {
        list($connection, $source) = $this->sharding->getUniqueShard($entityClass, []);

        $mongodb = $this->connector->get($connection);

        if ($pos = strpos($source, '.')) {
            $db = substr($source, 0, $pos);
            $collection = substr($source, $pos + 1);
        } else {
            $db = null;
            $collection = $source;
        }

        $collection = $mongodb->getPrefix() . $collection;

        $command = [
            'findAndModify' => 'auto_increment_id',
            'query'         => ['_id' => $collection],
            'update'        => ['$inc' => ['current_id' => $step]],
            'new'           => true,
            'upsert'        => true
        ];

        $id = $mongodb->command($command, $db)[0]['value']['current_id'];

        if ($id === $step) {
            $this->createAutoIncrementIndex($mongodb, $source);
        }

        return $id;
    }
}