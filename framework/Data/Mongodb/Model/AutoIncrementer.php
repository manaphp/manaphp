<?php
declare(strict_types=1);

namespace ManaPHP\Data\Mongodb\Model;

use ManaPHP\Data\Model\ShardingInterface;
use ManaPHP\Data\ModelManagerInterface;
use ManaPHP\Data\Mongodb\ConnectorInterface;
use ManaPHP\Data\MongodbInterface;
use ManaPHP\Di\Attribute\Inject;

class AutoIncrementer implements AutoIncrementerInterface
{
    #[Inject] protected ModelManagerInterface $modelManager;
    #[Inject] protected ShardingInterface $sharding;
    #[Inject] protected ConnectorInterface $connector;

    protected function createAutoIncrementIndex(MongodbInterface $mongodb, string $source): bool
    {
        $primaryKey = $this->modelManager->getPrimaryKey(static::class);

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

    public function getNext(string $model, int $step = 1): int
    {
        list($connection, $source) = $this->sharding->getUniqueShard($model, []);

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