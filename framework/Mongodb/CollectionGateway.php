<?php
declare(strict_types=1);

namespace ManaPHP\Mongodb;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Persistence\EntityMetadataInterface;
use ManaPHP\Persistence\ShardingInterface;
use ManaPHP\Persistence\ThoseInterface;

class CollectionGateway implements CollectionGatewayInterface
{
    #[Autowired] protected ThoseInterface $those;
    #[Autowired] protected MongodbConnectorInterface $connector;
    #[Autowired] protected ShardingInterface $sharding;
    #[Autowired] protected EntityMetadataInterface $entityMetadata;
    #[Autowired] protected EntityManagerInterface $entityManager;

    public function bulkInsert(string $entityClass, array $documents): int
    {
        if (!$documents) {
            return 0;
        }

        foreach ($documents as $i => $document) {
            $documents[$i] = $this->entityManager->normalizeDocument($entityClass, $document);
        }

        list($connection, $collection) = $this->sharding->getUniqueShard($entityClass, []);

        return $this->connector->get($connection)->bulkInsert($collection, $documents);
    }

    public function bulkUpdate(string $entityClass, array $documents): int
    {
        if (!$documents) {
            return 0;
        }

        $primaryKey = $this->entityMetadata->getPrimaryKey($entityClass);
        foreach ($documents as $i => $document) {
            if (!isset($document[$primaryKey])) {
                throw new MisuseException(['bulkUpdate `{1}` entity must set primary value', static::class]);
            }
            $documents[$i] = $this->entityManager->normalizeDocument($entityClass, $document);
        }

        $shards = $this->sharding->getAllShards($entityClass);

        $affected_count = 0;
        foreach ($shards as $connection => $collections) {
            $mongodb = $this->connector->get($connection);
            foreach ($collections as $collection) {
                $affected_count += $mongodb->bulkUpdate($collection, $documents, $primaryKey);
            }
        }

        return $affected_count;
    }

    public function bulkUpsert(string $entityClass, array $documents): int
    {
        if (!$documents) {
            return 0;
        }

        foreach ($documents as $i => $document) {
            $documents[$i] = $this->entityManager->normalizeDocument($entityClass, $document);
        }

        list($connection, $collection) = $this->sharding->getUniqueShard($entityClass, []);

        $primaryKey = $this->entityMetadata->getPrimaryKey($entityClass);
        return $this->connector->get($connection)->bulkUpsert($collection, $documents, $primaryKey);
    }

    /**
     * @param string $entityClass
     * @param array  $record =entity_var(new static)
     *
     * @return int
     */
    public function insert(string $entityClass, array $record): int
    {
        $record = $this->entityManager->normalizeDocument($entityClass, $record);

        list($connection, $collection) = $this->sharding->getUniqueShard($entityClass, $record);

        $mongodb = $this->connector->get($connection);
        $mongodb->insert($collection, $record);

        return 1;
    }

    public function delete(string $entityClass, array $conditions): int
    {
        $shards = $this->sharding->getMultipleShards($entityClass, $conditions);

        $affected_count = 0;
        foreach ($shards as $connection => $tables) {
            $db = $this->connector->get($connection);

            foreach ($tables as $table) {
                $affected_count += $db->delete($table, $conditions);
            }
        }

        return $affected_count;
    }

    public function update(string $entityClass, array $fieldValues, array $conditions): int
    {
        $shards = $this->sharding->getMultipleShards($entityClass, $conditions);

        $affected_count = 0;
        foreach ($shards as $connection => $tables) {
            $db = $this->connector->get($connection);

            foreach ($tables as $table) {
                $affected_count += $db->update($table, $fieldValues, $conditions);
            }
        }

        return $affected_count;
    }
}