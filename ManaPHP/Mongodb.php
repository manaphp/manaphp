<?php

namespace ManaPHP;

use ManaPHP\Mongodb\Exception as MongodbException;
use MongoDB\Driver\Exception\RuntimeException;

/**
 * Class Mongodb
 * @package ManaPHP
 * @property-read \ManaPHP\DiInterface $di
 */
class Mongodb extends Component implements MongodbInterface
{
    /**
     * @var string
     */
    protected $_dsn;

    /**
     * @var string
     */
    protected $_default_db;

    /**
     * Mongodb constructor.
     *
     * @param string $dsn
     */
    public function __construct($dsn = 'mongodb://127.0.0.1:27017/')
    {
        $this->_dsn = $dsn;

        $path = parse_url($dsn, PHP_URL_PATH);
        $this->_default_db = ($path !== '/' && $path !== null) ? (string)substr($path, 1) : null;

        $this->poolManager->add($this, $this->di->getInstance('ManaPHP\Mongodb\Connection', [$this->_dsn]));
    }

    /**
     * @return string|null
     */
    public function getDefaultDb()
    {
        return $this->_default_db;
    }

    /**
     * @param string $source
     * @param array  $document
     *
     * @return int
     */
    public function insert($source, $document)
    {
        $namespace = strpos($source, '.') !== false ? $source : ($this->_default_db . '.' . $source);

        $this->eventsManager->fireEvent('mongodb:beforeInsert', $this, ['namespace' => $namespace]);

        /** @var \ManaPHP\Mongodb\ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $count = $connection->insert($namespace, $document);
        } finally {
            $this->poolManager->push($this, $connection);
        }
        $this->eventsManager->fireEvent('mongodb:afterInsert', $this, ['namespace' => $namespace]);

        $this->logger->info(compact('count', 'namespace', 'document'), 'mongodb.insert');

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
        $namespace = strpos($source, '.') !== false ? $source : ($this->_default_db . '.' . $source);

        $this->eventsManager->fireEvent('mongodb:beforeBulkInsert', $this, ['namespace' => $namespace]);

        /** @var \ManaPHP\Mongodb\ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $count = $connection->bulkInsert($namespace, $documents);
        } finally {
            $this->poolManager->push($this, $connection);
        }
        $this->eventsManager->fireEvent('mongodb:afterBulkInsert', $this, ['namespace' => $namespace]);

        $this->logger->info(compact('namespace', 'documents', 'count'), 'mongodb.bulk.insert');
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
        $namespace = strpos($source, '.') !== false ? $source : ($this->_default_db . '.' . $source);

        $this->eventsManager->fireEvent('mongodb:beforeUpdate', $this, ['namespace' => $namespace]);

        /** @var \ManaPHP\Mongodb\ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $count = $connection->update($namespace, $document, $filter);
        } finally {
            $this->poolManager->push($this, $connection);
        }
        $this->eventsManager->fireEvent('mongodb:afterUpdate', $this);
        $this->logger->info(compact('namespace', 'document', 'filter', 'count'), 'mongodb.update');
        return $count;
    }

    /**
     * @param string $source
     * @param array  $documents
     * @param string $primaryKey
     *
     * @return int
     * @throws \ManaPHP\Mongodb\Exception
     */
    public function bulkUpdate($source, $documents, $primaryKey)
    {
        $namespace = strpos($source, '.') !== false ? $source : ($this->_default_db . '.' . $source);

        $this->eventsManager->fireEvent('mongodb:beforeBulkUpdate', $this, ['namespace' => $namespace]);

        /** @var \ManaPHP\Mongodb\ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $count = $connection->bulkUpdate($namespace, $documents, $primaryKey);
        } finally {
            $this->poolManager->push($this, $connection);
        }
        $this->eventsManager->fireEvent('mongodb:afterBulkUpdate', $this, ['namespace' => $namespace]);

        $this->logger->info(compact('namespace', 'documents', 'primaryKey', 'count'), 'mongodb.bulk.update');
        return $count;
    }

    /**
     * @param string $source
     * @param array  $document
     * @param string $primaryKey
     *
     * @return int
     * @throws \ManaPHP\Mongodb\Exception
     */
    public function upsert($source, $document, $primaryKey)
    {
        $namespace = strpos($source, '.') !== false ? $source : ($this->_default_db . '.' . $source);

        $this->eventsManager->fireEvent('mongodb:beforeUpsert', $this, ['namespace' => $namespace]);

        /** @var \ManaPHP\Mongodb\ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $count = $connection->upsert($namespace, $document, $primaryKey);
        } finally {
            $this->poolManager->push($this, $connection);
        }
        $this->eventsManager->fireEvent('mongodb:afterUpsert', $this);

        $this->logger->info(compact('count', 'namespace', 'document'), 'mongodb.upsert');
        return $count;
    }

    /**
     * @param string $source
     * @param array  $documents
     * @param string $primaryKey
     *
     * @return int
     * @throws \ManaPHP\Mongodb\Exception
     */
    public function bulkUpsert($source, $documents, $primaryKey)
    {
        $namespace = strpos($source, '.') !== false ? $source : ($this->_default_db . '.' . $source);

        $this->eventsManager->fireEvent('mongodb:beforeBulkUpsert', $this, ['namespace' => $namespace]);

        /** @var \ManaPHP\Mongodb\ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $count = $connection->bulkUpsert($namespace, $documents, $primaryKey);
        } finally {
            $this->poolManager->push($this, $connection);
        }
        $this->eventsManager->fireEvent('mongodb:afterBulkUpsert', $this);

        $this->logger->info(compact('count', 'namespace', 'documents'), 'mongodb.bulk.upsert');
        return $count;
    }

    /**
     * @param string $source
     * @param array  $filter
     *
     * @return int
     * @throws \ManaPHP\Mongodb\Exception
     */
    public function delete($source, $filter)
    {
        $namespace = strpos($source, '.') !== false ? $source : ($this->_default_db . '.' . $source);

        $this->eventsManager->fireEvent('mongodb:beforeDelete', $this, ['namespace' => $namespace]);

        /** @var \ManaPHP\Mongodb\ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $count = $connection->delete($namespace, $filter);
        } finally {
            $this->poolManager->push($this, $connection);
        }
        $this->eventsManager->fireEvent('mongodb:afterDelete', $this);

        $this->logger->info(compact('namespace', 'filter', 'count'), 'mongodb.delete');
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
        $namespace = strpos($source, '.') !== false ? $source : ($this->_default_db . '.' . $source);

        $this->eventsManager->fireEvent('mongodb:beforeQuery', $this, compact('namespace', 'filter', 'options'));

        /** @var \ManaPHP\Mongodb\ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $start_time = microtime(true);
            $result = $connection->fetchAll($namespace, $filter, $options, $secondaryPreferred);
            $elapsed = round(microtime(true) - $start_time, 3);
        } finally {
            $this->poolManager->push($this, $connection);
        }

        $this->eventsManager->fireEvent('mongodb:afterQuery', $this, compact('namespace', 'filter', 'options', 'result', 'elapsed'));

        $this->logger->debug(compact('namespace', 'filter', 'options', 'result', 'elapsed'), 'mongodb.query');
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
            $db = $this->_default_db;
        }

        $this->eventsManager->fireEvent('mongodb:beforeCommand', $this, compact('db', 'command'));

        /** @var \ManaPHP\Mongodb\ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $start_time = microtime(true);
            $result = $connection->command($command, $db);
            $elapsed = round(microtime(true) - $start_time, 3);
        } finally {
            $this->poolManager->push($this, $connection);
        }
        $this->eventsManager->fireEvent('mongodb:afterCommand', $this, compact('db', 'command', 'result', 'elapsed'));

        $count = count($result);
        $command_name = key($command);
        if (strpos('ping,aggregate,count,distinct,group,mapReduce,geoNear,geoSearch,find,' .
                'authenticate,listDatabases,listCollections,listIndexes', $command_name) !== false) {
            $this->logger->debug(compact('db', 'command', 'count', 'elapsed'), 'mongodb.command.' . $command_name);
        } else {
            $this->logger->info(compact('db', 'command', 'count', 'elapsed'), 'mongodb.command.' . $command_name);
        }

        return $result;
    }

    /**
     * @param string $source
     * @param array  $pipeline
     * @param array  $options
     *
     * @return array
     * @throws \ManaPHP\Mongodb\Exception
     */
    public function aggregate($source, $pipeline, $options = [])
    {
        if ($pos = strpos($source, '.')) {
            $db = substr($source, 0, $pos);
            $collection = substr($source, $pos + 1);
        } else {
            $db = $this->_default_db;
            $collection = $source;
        }

        try {
            $command = ['aggregate' => $collection, 'pipeline' => $pipeline];
            if ($options) {
                $command = array_merge($command, $options);
            }
            if (!isset($command['cursor'])) {
                $command['cursor'] = ['batchSize' => 1000];
            }
            return $this->command($command, $db);
        } catch (RuntimeException $e) {
            throw new MongodbException([
                '`:aggregate` aggregate for `:collection` collection failed: :msg',
                'aggregate' => json_encode($pipeline),
                'collection' => $source,
                'msg' => $e->getMessage()
            ]);
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
            $db = $this->_default_db;
            $collection = $source;
        }

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

    /**
     * @return array
     */
    public function listDatabases()
    {
        $databases = [];
        $result = $this->command(['listDatabases' => 1], 'admin');
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
        $result = $this->command(['listCollections' => 1], $db);
        foreach ($result as $collection) {
            $collections[] = $collection['name'];
        }

        return $collections;
    }

    /**
     * @param string $collection
     *
     * @return \ManaPHP\Mongodb\Query
     */
    public function query($collection = null)
    {
        return $this->_di->get('ManaPHP\Mongodb\Query', [$this])->from($collection);
    }
}