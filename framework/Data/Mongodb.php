<?php
declare(strict_types=1);

namespace ManaPHP\Data;

use ManaPHP\Component;
use ManaPHP\Data\Mongodb\ConnectionMakerInterface;
use ManaPHP\Data\Mongodb\Exception as MongodbException;
use ManaPHP\Data\Mongodb\Query;
use ManaPHP\Data\Mongodb\QueryMakerInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Event\EventTrait;
use ManaPHP\Exception\NonCloneableException;
use ManaPHP\Pool\ManagerInterface;
use MongoDB\Driver\Exception\RuntimeException;

class Mongodb extends Component implements MongodbInterface
{
    use EventTrait;

    #[Inject] protected ManagerInterface $poolManager;
    #[Inject] protected MakerInterface $maker;
    #[Inject] protected ConnectionMakerInterface $connectionMaker;
    #[Inject] protected QueryMakerInterface $queryMaker;

    protected string $uri;
    protected string $prefix;
    protected string $db;

    public function __construct(string $uri = 'mongodb://127.0.0.1:27017/')
    {
        $this->uri = $uri;

        if (preg_match('#[?&]prefix=(\w+)#', $uri, $matches)) {
            $this->prefix = $matches[1];
        }

        $path = parse_url($uri, PHP_URL_PATH);
        $this->db = ($path !== '/' && $path !== null) ? substr($path, 1) : null;

        $sample = $this->connectionMaker->make([$this->uri]);
        $this->poolManager->add($this, $sample);
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

        $this->fireEvent('mongodb:inserting', compact('namespace'));

        /** @var \ManaPHP\Data\Mongodb\ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $count = $connection->insert($namespace, $document);
        } finally {
            $this->poolManager->push($this, $connection);
        }
        $this->fireEvent('mongodb:inserted', compact('count', 'namespace', 'document'));

        return $count;
    }

    public function bulkInsert(string $source, array $documents): int
    {
        $namespace = $this->completeNamespace($source);

        $this->fireEvent('mongodb:bulkWriting', compact('namespace', 'documents'));
        $this->fireEvent('mongodb:bulkInserting', compact('namespace', 'documents'));

        /** @var \ManaPHP\Data\Mongodb\ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $count = $connection->bulkInsert($namespace, $documents);
        } finally {
            $this->poolManager->push($this, $connection);
        }

        $this->fireEvent('mongodb:bulkInserted', compact('namespace', 'documents', 'count'));
        $this->fireEvent('mongodb:bulkWritten', compact('namespace', 'documents', 'count'));

        return $count;
    }

    public function update(string $source, array $document, array $filter): int
    {
        $namespace = $this->completeNamespace($source);

        $this->fireEvent('mongodb:updating', compact('namespace', 'document', 'filter'));

        /** @var \ManaPHP\Data\Mongodb\ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $count = $connection->update($namespace, $document, $filter);
        } finally {
            $this->poolManager->push($this, $connection);
        }
        $this->fireEvent('mongodb:updated', compact('namespace', 'document', 'filter', 'count'));
        return $count;
    }

    public function bulkUpdate(string $source, array $documents, string $primaryKey): int
    {
        $namespace = $this->completeNamespace($source);

        $this->fireEvent('mongodb:bulkWriting', compact('namespace', 'documents'));
        $this->fireEvent('mongodb:bulkUpdating', compact('namespace', 'documents'));

        /** @var \ManaPHP\Data\Mongodb\ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $count = $connection->bulkUpdate($namespace, $documents, $primaryKey);
        } finally {
            $this->poolManager->push($this, $connection);
        }

        $this->fireEvent('mongodb:bulkUpdated', compact('namespace', 'documents', 'primaryKey', 'count'));
        $this->fireEvent('mongodb:bulkWritten', compact('namespace', 'documents', 'primaryKey', 'count'));

        return $count;
    }

    public function upsert(string $source, array $document, string $primaryKey): int
    {
        $namespace = $this->completeNamespace($source);

        $this->fireEvent('mongodb:upserting', compact('namespace', 'document'));

        /** @var \ManaPHP\Data\Mongodb\ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $count = $connection->upsert($namespace, $document, $primaryKey);
        } finally {
            $this->poolManager->push($this, $connection);
        }
        $this->fireEvent('mongodb:upserted', compact('count', 'namespace', 'document'));

        return $count;
    }

    public function bulkUpsert(string $source, array $documents, string $primaryKey): int
    {
        $namespace = $this->completeNamespace($source);

        $this->fireEvent('mongodb:bulkWriting', compact('namespace', 'documents'));
        $this->fireEvent('mongodb:bulkUpserting', compact('namespace', 'documents'));

        /** @var \ManaPHP\Data\Mongodb\ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $count = $connection->bulkUpsert($namespace, $documents, $primaryKey);
        } finally {
            $this->poolManager->push($this, $connection);
        }
        $this->fireEvent('mongodb:bulkUpserted', compact('namespace', 'documents', 'count'));
        $this->fireEvent('mongodb:bulkWritten', compact('namespace', 'documents', 'count'));

        return $count;
    }

    public function delete(string $source, array $filter): int
    {
        $namespace = $this->completeNamespace($source);

        $this->fireEvent('mongodb:deleting', compact('namespace', 'filter'));

        /** @var \ManaPHP\Data\Mongodb\ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $count = $connection->delete($namespace, $filter);
        } finally {
            $this->poolManager->push($this, $connection);
        }
        $this->fireEvent('mongodb:deleted', compact('namespace', 'filter', 'count'));

        return $count;
    }

    public function fetchAll(string $source, array $filter = [], array $options = [], bool $secondaryPreferred = true
    ): array {
        $namespace = $this->completeNamespace($source);

        $this->fireEvent('mongodb:querying', compact('namespace', 'filter', 'options'));

        /** @var \ManaPHP\Data\Mongodb\ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $start_time = microtime(true);
            $result = $connection->fetchAll($namespace, $filter, $options, $secondaryPreferred);
            $elapsed = round(microtime(true) - $start_time, 3);
        } finally {
            $this->poolManager->push($this, $connection);
        }

        $this->fireEvent('mongodb:queried', compact('namespace', 'filter', 'options', 'result', 'elapsed'));

        return $result;
    }

    public function command(array $command, ?string $db = null): array
    {
        if (!$db) {
            $db = $this->db;
        }

        $this->fireEvent('mongodb:commanding', compact('db', 'command'));

        /** @var \ManaPHP\Data\Mongodb\ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $start_time = microtime(true);
            $result = $connection->command($command, $db);
            $elapsed = round(microtime(true) - $start_time, 3);
        } finally {
            $this->poolManager->push($this, $connection);
        }

        $count = count($result);
        $this->fireEvent('mongodb:commanded', compact('db', 'command', 'result', 'count', 'elapsed'));

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
        return $this->queryMaker->make([$this])->from($collection);
    }
}