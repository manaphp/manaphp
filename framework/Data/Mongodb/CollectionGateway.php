<?php
declare(strict_types=1);

namespace ManaPHP\Data\Mongodb;

use ManaPHP\Component;
use ManaPHP\Exception\MisuseException;

/**
 * @property-read \ManaPHP\Data\Model\ThoseInterface     $those
 * @property-read \ManaPHP\Data\Mongodb\FactoryInterface $mongodbFactory
 * @property-read \ManaPHP\Data\Model\ShardingInterface  $sharding
 */
class CollectionGateway extends Component implements CollectionGatewayInterface
{
    protected function getThat(string $model): Model
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->those->get($model);
    }

    public function bulkInsert(string $model, array $documents): int
    {
        if (!$documents) {
            return 0;
        }

        $that = $this->getThat($model);

        foreach ($documents as $i => $document) {
            $documents[$i] = $that->normalizeDocument($document);
        }

        list($connection, $collection) = $this->sharding->getUniqueShard($model, []);

        return $this->mongodbFactory->get($connection)->bulkInsert($collection, $documents);
    }

    public function bulkUpdate(string $model, array $documents): int
    {
        if (!$documents) {
            return 0;
        }

        $that = $this->getThat($model);

        $primaryKey = $that->primaryKey();
        foreach ($documents as $i => $document) {
            if (!isset($document[$primaryKey])) {
                throw new MisuseException(['bulkUpdate `%s` model must set primary value', static::class]);
            }
            $documents[$i] = $that->normalizeDocument($document);
        }

        $shards = $this->sharding->getAllShards($model);

        $affected_count = 0;
        foreach ($shards as $connection => $collections) {
            $mongodb = $this->mongodbFactory->get($connection);
            foreach ($collections as $collection) {
                $affected_count += $mongodb->bulkUpdate($collection, $documents, $primaryKey);
            }
        }

        return $affected_count;
    }

    public function bulkUpsert(string $model, array $documents): int
    {
        if (!$documents) {
            return 0;
        }

        $that = $this->getThat($model);

        foreach ($documents as $i => $document) {
            $documents[$i] = $that->normalizeDocument($document);
        }

        list($connection, $collection) = $this->sharding->getUniqueShard($model, []);

        return $this->mongodbFactory->get($connection)->bulkUpsert($collection, $documents, $that->primaryKey());
    }

    /**
     * @param string $model
     * @param array  $record =model_var(new static)
     *
     * @return int
     */
    public function insert(string $model, array $record): int
    {
        $that = $this->getThat($model);

        $record = $that->normalizeDocument($record);

        list($connection, $collection) = $this->sharding->getUniqueShard($model, $record);

        $mongodb = $this->mongodbFactory->get($connection);
        $mongodb->insert($collection, $record);

        return 1;
    }

    public function delete(string $model, array $conditions): int
    {
        $shards = $this->sharding->getMultipleShards($model, $conditions);

        $affected_count = 0;
        foreach ($shards as $connection => $tables) {
            $db = $this->mongodbFactory->get($connection);

            foreach ($tables as $table) {
                $affected_count += $db->delete($table, $conditions);
            }
        }

        return $affected_count;
    }

    public function update(string $model, array $fieldValues, array $conditions): int
    {
        $shards = $this->sharding->getMultipleShards($model, $conditions);

        $affected_count = 0;
        foreach ($shards as $connection => $tables) {
            $db = $this->mongodbFactory->get($connection);

            foreach ($tables as $table) {
                $affected_count += $db->update($table, $fieldValues, $conditions);
            }
        }

        return $affected_count;
    }
}