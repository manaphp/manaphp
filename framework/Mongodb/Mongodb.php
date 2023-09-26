<?php
declare(strict_types=1);

namespace ManaPHP\Mongodb;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Exception\NonCloneableException;
use ManaPHP\Mongodb\Event\MongodbBulkInserted;
use ManaPHP\Mongodb\Event\MongodbBulkInserting;
use ManaPHP\Mongodb\Event\MongodbBulkUpdating;
use ManaPHP\Mongodb\Event\MongodbBulkUpserted;
use ManaPHP\Mongodb\Event\MongodbBulkUpserting;
use ManaPHP\Mongodb\Event\MongodbBulkWriting;
use ManaPHP\Mongodb\Event\MongodbBulkWritten;
use ManaPHP\Mongodb\Event\MongodbCommanded;
use ManaPHP\Mongodb\Event\MongodbCommanding;
use ManaPHP\Mongodb\Event\MongodbDeleted;
use ManaPHP\Mongodb\Event\MongodbDeleting;
use ManaPHP\Mongodb\Event\MongodbInserted;
use ManaPHP\Mongodb\Event\MongodbInserting;
use ManaPHP\Mongodb\Event\MongodbQueried;
use ManaPHP\Mongodb\Event\MongodbQuerying;
use ManaPHP\Mongodb\Event\MongodbUpdated;
use ManaPHP\Mongodb\Event\MongodbUpdating;
use ManaPHP\Mongodb\Exception as MongodbException;
use ManaPHP\Pooling\PoolManagerInterface;
use MongoDB\Driver\Exception\RuntimeException;
use Psr\EventDispatcher\EventDispatcherInterface;

class Mongodb implements MongodbInterface
{
    #[Inject] protected EventDispatcherInterface $eventDispatcher;
    #[Inject] protected PoolManagerInterface $poolManager;
    #[Inject] protected MakerInterface $maker;

    #[Value] protected string $uri = 'mongodb://127.0.0.1:27017/';
    protected string $prefix;
    protected string $db;

    /** @noinspection PhpTypedPropertyMightBeUninitializedInspection */
    public function __construct()
    {
        if (preg_match('#[?&]prefix=(\w+)#', $this->uri, $matches)) {
            $this->prefix = $matches[1];
        }

        $path = parse_url($this->uri, PHP_URL_PATH);
        $this->db = ($path !== '/' && $path !== null) ? substr($path, 1) : null;

        $this->poolManager->add($this, [Connection::class, ['uri' => $this->uri]]);
    }

    public function __clone()
    {
        throw new NonCloneableException($this);
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getDb(): string
    {
        return $this->db;
    }

    protected function completeNamespace(string $source): string
    {
        if (str_contains($source, '.')) {
            return str_replace('.', '.' . $this->prefix, $source);
        } else {
            return $this->db . '.' . $this->prefix . $source;
        }
    }

    public function insert(string $source, array $document): int
    {
        $namespace = $this->completeNamespace($source);

        $this->eventDispatcher->dispatch(new MongodbInserting($this, $namespace));

        /** @var ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $count = $connection->insert($namespace, $document);
        } finally {
            $this->poolManager->push($this, $connection);
        }
        $this->eventDispatcher->dispatch(new MongodbInserted($this, $count, $namespace, $document));

        return $count;
    }

    public function bulkInsert(string $source, array $documents): int
    {
        $namespace = $this->completeNamespace($source);

        $this->eventDispatcher->dispatch(new MongodbBulkWriting($this, $namespace, $documents));
        $this->eventDispatcher->dispatch(new MongodbBulkInserting($this, $namespace, $documents));

        /** @var ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $count = $connection->bulkInsert($namespace, $documents);
        } finally {
            $this->poolManager->push($this, $connection);
        }

        $this->eventDispatcher->dispatch(new MongodbBulkInserted($this, $namespace, $documents, $count));
        $this->eventDispatcher->dispatch(new MongodbBulkWritten($this, $namespace, $documents, $count));

        return $count;
    }

    public function update(string $source, array $document, array $filter): int
    {
        $namespace = $this->completeNamespace($source);

        $this->eventDispatcher->dispatch(new MongodbUpdating($this, $namespace, $document, $filter));

        /** @var ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $count = $connection->update($namespace, $document, $filter);
        } finally {
            $this->poolManager->push($this, $connection);
        }
        $this->eventDispatcher->dispatch(new MongodbUpdated($this, $namespace, $document, $filter, $count));

        return $count;
    }

    public function bulkUpdate(string $source, array $documents, string $primaryKey): int
    {
        $namespace = $this->completeNamespace($source);

        $this->eventDispatcher->dispatch(new MongodbBulkWriting($this, $namespace, $documents));
        $this->eventDispatcher->dispatch(new MongodbBulkUpdating($this, $namespace, $documents));

        /** @var ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $count = $connection->bulkUpdate($namespace, $documents, $primaryKey);
        } finally {
            $this->poolManager->push($this, $connection);
        }

        $this->eventDispatcher->dispatch(new MongodbBulkInserted($this, $namespace, $documents, $count));
        $this->eventDispatcher->dispatch(new MongodbBulkWritten($this, $namespace, $documents, $count));

        return $count;
    }

    public function upsert(string $source, array $document, string $primaryKey): int
    {
        $namespace = $this->completeNamespace($source);

        $this->eventDispatcher->dispatch(new MongodbBulkUpserting($this, $namespace, [$document]));

        /** @var ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $count = $connection->upsert($namespace, $document, $primaryKey);
        } finally {
            $this->poolManager->push($this, $connection);
        }

        $this->eventDispatcher->dispatch(new MongodbBulkUpserted($this, $namespace, [$document], $count));

        return $count;
    }

    public function bulkUpsert(string $source, array $documents, string $primaryKey): int
    {
        $namespace = $this->completeNamespace($source);

        $this->eventDispatcher->dispatch(new MongodbBulkWriting($this, $namespace, $documents));
        $this->eventDispatcher->dispatch(new MongodbBulkUpserting($this, $namespace, $documents));

        /** @var ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $count = $connection->bulkUpsert($namespace, $documents, $primaryKey);
        } finally {
            $this->poolManager->push($this, $connection);
        }
        $this->eventDispatcher->dispatch(new MongodbBulkUpserted($this, $namespace, $documents, $count));
        $this->eventDispatcher->dispatch(new MongodbBulkWritten($this, $namespace, $documents, $count));

        return $count;
    }

    public function delete(string $source, array $filter): int
    {
        $namespace = $this->completeNamespace($source);

        $this->eventDispatcher->dispatch(new MongodbDeleting($this, $namespace, $filter));

        /** @var ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $count = $connection->delete($namespace, $filter);
        } finally {
            $this->poolManager->push($this, $connection);
        }
        $this->eventDispatcher->dispatch(new MongodbDeleted($this, $namespace, $filter, $count));

        return $count;
    }

    public function fetchAll(string $source, array $filter = [], array $options = [], bool $secondaryPreferred = true
    ): array {
        $namespace = $this->completeNamespace($source);

        $this->eventDispatcher->dispatch(new MongodbQuerying($this, $namespace, $filter, $options));

        /** @var ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $start_time = microtime(true);
            $result = $connection->fetchAll($namespace, $filter, $options, $secondaryPreferred);
            $elapsed = round(microtime(true) - $start_time, 3);
        } finally {
            $this->poolManager->push($this, $connection);
        }

        $this->eventDispatcher->dispatch(new MongodbQueried($this, $namespace, $filter, $options, $result, $elapsed));

        return $result;
    }

    public function command(array $command, ?string $db = null): array
    {
        if (!$db) {
            $db = $this->db;
        }

        $this->eventDispatcher->dispatch(new MongodbCommanding($this, $db, $command));

        /** @var ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $start_time = microtime(true);
            $result = $connection->command($command, $db);
            $elapsed = round(microtime(true) - $start_time, 3);
        } finally {
            $this->poolManager->push($this, $connection);
        }

        $count = count($result);
        $this->eventDispatcher->dispatch(new MongodbCommanded($this, $db, $command, $result, $count, $elapsed));

        return $result;
    }

    public function aggregate(string $source, array $pipeline, array $options = []): array
    {
        if ($pos = strpos($source, '.')) {
            $db = substr($source, 0, $pos);
            $collection = substr($source, $pos + 1);
        } else {
            $db = $this->db;
            $collection = $source;
        }

        $collection = $this->prefix . $collection;
        try {
            $command = ['aggregate' => $collection, 'pipeline' => $pipeline];
            if ($options) {
                $command = array_merge($command, $options);
            }
            $command['cursor'] ??= ['batchSize' => 1000];
            return $this->command($command, $db);
        } catch (RuntimeException $e) {
            throw new MongodbException(
                ['`%s` aggregate for `%s` collection failed: %s', json_stringify($pipeline), $source, $e->getMessage()]
            );
        }
    }

    public function truncate(string $source): bool
    {
        if ($pos = strpos($source, '.')) {
            $db = substr($source, 0, $pos);
            $collection = substr($source, $pos + 1);
        } else {
            $db = $this->db;
            $collection = $source;
        }

        $collection = $this->prefix . $collection;
        try {
            $this->command(['drop' => $collection], $db);
            return true;
        } catch (RuntimeException $e) {
            /**
             * https://github.com/mongodb/mongo/blob/master/src/mongo/base/error_codes.err
             * error_code("NamespaceNotFound", 26)
             */
            if ($e->getCode() === 26) {
                return true;
            } else {
                throw $e;
            }
        }
    }

    public function listDatabases(): array
    {
        $databases = [];
        $result = $this->command(['listDatabases' => 1], 'admin');
        foreach ((array)$result[0]['databases'] as $database) {
            $databases[] = $database['name'];
        }

        return $databases;
    }

    public function listCollections(?string $db = null): array
    {
        $collections = [];
        $result = $this->command(['listCollections' => 1], $db);
        if ($this->prefix === '') {
            foreach ($result as $collection) {
                $collections[] = $collection['name'];
            }
        } else {
            $prefix = $this->prefix;
            $prefix_len = strlen($prefix);
            foreach ($result as $collection) {
                $name = $collection['name'];
                if (str_starts_with($name, $prefix)) {
                    $collections[] = substr($name, $prefix_len);
                }
            }
        }

        return $collections;
    }

    public function query(?string $collection = null): Query
    {
        return $this->maker->make(Query::class, [$this])->from($collection);
    }
}