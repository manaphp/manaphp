<?php

namespace ManaPHP\Data;

use ManaPHP\Component;
use ManaPHP\Data\Mongodb\Exception as MongodbException;
use ManaPHP\Exception\NonCloneableException;
use MongoDB\Driver\Exception\RuntimeException;

/**
 * @property-read \ManaPHP\Pool\ManagerInterface $poolManager
 */
class Mongodb extends Component implements MongodbInterface
{
    /**
     * @var string
     */
    protected $uri;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * @var string
     */
    protected $db;

    /**
     * @param string $uri
     */
    public function __construct($uri = 'mongodb://127.0.0.1:27017/')
    {
        $this->uri = $uri;

        if (preg_match('#[?&]prefix=(\w+)#', $uri, $matches)) {
            $this->prefix = $matches[1];
        }

        $path = parse_url($uri, PHP_URL_PATH);
        $this->db = ($path !== '/' && $path !== null) ? (string)substr($path, 1) : null;

        $this->poolManager->add($this, $this->getNew('ManaPHP\Data\Mongodb\Connection', [$this->uri]));
    }

    public function __destruct()
    {
        $this->poolManager->remove($this);
    }

    public function __clone()
    {
        throw new NonCloneableException($this);
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @return string|null
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * @param string $source
     *
     * @return string
     */
    protected function completeNamespace($source)
    {
        if (str_contains($source, '.')) {
            return str_replace('.', '.' . $this->prefix, $source);
        } else {
            return $this->db . '.' . $this->prefix . $source;
        }
    }

    /**
     * @param string $source
     * @param array  $document
     *
     * @return int
     */
    public function insert($source, $document)
    {
        $namespace = $this->self->completeNamespace($source);

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

    /**
     * @param string  $source
     * @param array[] $documents
     *
     * @return int
     */
    public function bulkInsert($source, $documents)
    {
        $namespace = $this->self->completeNamespace($source);

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

    /**
     * @param string $source
     * @param array  $document
     * @param array  $filter
     *
     * @return int
     */
    public function update($source, $document, $filter)
    {
        $namespace = $this->self->completeNamespace($source);

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

    /**
     * @param string $source
     * @param array  $documents
     * @param string $primaryKey
     *
     * @return int
     * @throws \ManaPHP\Data\Mongodb\Exception
     */
    public function bulkUpdate($source, $documents, $primaryKey)
    {
        $namespace = $this->self->completeNamespace($source);

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

    /**
     * @param string $source
     * @param array  $document
     * @param string $primaryKey
     *
     * @return int
     * @throws \ManaPHP\Data\Mongodb\Exception
     */
    public function upsert($source, $document, $primaryKey)
    {
        $namespace = $this->self->completeNamespace($source);

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

    /**
     * @param string $source
     * @param array  $documents
     * @param string $primaryKey
     *
     * @return int
     * @throws \ManaPHP\Data\Mongodb\Exception
     */
    public function bulkUpsert($source, $documents, $primaryKey)
    {
        $namespace = $this->self->completeNamespace($source);

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

    /**
     * @param string $source
     * @param array  $filter
     *
     * @return int
     * @throws \ManaPHP\Data\Mongodb\Exception
     */
    public function delete($source, $filter)
    {
        $namespace = $this->self->completeNamespace($source);

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

    /**
     * @param string   $source
     * @param array    $filter
     * @param array    $options
     * @param bool|int $secondaryPreferred
     *
     * @return array[]
     */
    public function fetchAll($source, $filter = [], $options = [], $secondaryPreferred = true)
    {
        $namespace = $this->self->completeNamespace($source);

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

    /**
     * @param array  $command
     * @param string $db
     *
     * @return array[]
     */
    public function command($command, $db = null)
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

    /**
     * @param string $source
     * @param array  $pipeline
     * @param array  $options
     *
     * @return array
     * @throws \ManaPHP\Data\Mongodb\Exception
     */
    public function aggregate($source, $pipeline, $options = [])
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
            if (!isset($command['cursor'])) {
                $command['cursor'] = ['batchSize' => 1000];
            }
            return $this->self->command($command, $db);
        } catch (RuntimeException $e) {
            throw new MongodbException(
                ['`%s` aggregate for `%s` collection failed: %s', json_stringify($pipeline), $source, $e->getMessage()]
            );
        }
    }

    /**
     * @param string $source
     *
     * @return bool
     */
    public function truncate($source)
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
            $this->self->command(['drop' => $collection], $db);
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

    /**
     * @return array
     */
    public function listDatabases()
    {
        $databases = [];
        $result = $this->self->command(['listDatabases' => 1], 'admin');
        foreach ((array)$result[0]['databases'] as $database) {
            $databases[] = $database['name'];
        }

        return $databases;
    }

    /**
     * @param string $db
     *
     * @return array
     */
    public function listCollections($db = null)
    {
        $collections = [];
        $result = $this->self->command(['listCollections' => 1], $db);
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

    /**
     * @param string $collection
     *
     * @return \ManaPHP\Data\Mongodb\Query
     */
    public function query($collection = null)
    {
        return $this->getNew('ManaPHP\Data\Mongodb\Query', [$this])->from($collection);
    }
}