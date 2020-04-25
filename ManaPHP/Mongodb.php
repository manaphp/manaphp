<?php

namespace ManaPHP;

use ManaPHP\Mongodb\Exception as MongodbException;
use MongoDB\Driver\Exception\RuntimeException;

/**
 * Class Mongodb
 *
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
    protected $_prefix;

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

        if (preg_match('#[?&]prefix=(\w+)#', $dsn, $matches)) {
            $this->_prefix = $matches[1];
        }

        $path = parse_url($dsn, PHP_URL_PATH);
        $this->_default_db = ($path !== '/' && $path !== null) ? (string)substr($path, 1) : null;

        $this->poolManager->add($this, $this->di->get('ManaPHP\Mongodb\Connection', [$this->_dsn]));
    }

    public function __destruct()
    {
        $this->poolManager->remove($this);
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->_prefix;
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
     *
     * @return string
     */
    protected function _completeNamespace($source)
    {
        if (strpos($source, '.') === false) {
            return $this->_default_db . '.' . $this->_prefix . $source;
        } else {
            return str_replace('.', '.' . $this->_prefix, $source);
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
        $namespace = $this->_completeNamespace($source);

        $this->fireEvent('mongodb:inserting', ['namespace' => $namespace]);

        /** @var \ManaPHP\Mongodb\ConnectionInterface $connection */
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
        $namespace = $this->_completeNamespace($source);

        $this->fireEvent('mongodb:bulkWriting', ['namespace' => $namespace]);
        $this->fireEvent('mongodb:bulkInserting', ['namespace' => $namespace]);

        /** @var \ManaPHP\Mongodb\ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $count = $connection->bulkInsert($namespace, $documents);
        } finally {
            $this->poolManager->push($this, $connection);
        }
        $data = compact('namespace', 'documents', 'count');
        $this->fireEvent('mongodb:bulkInserted', $data);
        $this->fireEvent('mongodb:bulkWritten', $data);

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
        $namespace = $this->_completeNamespace($source);

        $this->fireEvent('mongodb:updating', ['namespace' => $namespace]);

        /** @var \ManaPHP\Mongodb\ConnectionInterface $connection */
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
     * @throws \ManaPHP\Mongodb\Exception
     */
    public function bulkUpdate($source, $documents, $primaryKey)
    {
        $namespace = $this->_completeNamespace($source);

        $this->fireEvent('mongodb:bulkWriting', ['namespace' => $namespace]);
        $this->fireEvent('mongodb:bulkUpdating', ['namespace' => $namespace]);

        /** @var \ManaPHP\Mongodb\ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $count = $connection->bulkUpdate($namespace, $documents, $primaryKey);
        } finally {
            $this->poolManager->push($this, $connection);
        }

        $data = compact('namespace', 'documents', 'primaryKey', 'count');
        $this->fireEvent('mongodb:bulkUpdated', $data);
        $this->fireEvent('mongodb:bulkWritten', $data);

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
        $namespace = $this->_completeNamespace($source);

        $this->fireEvent('mongodb:upserting', ['namespace' => $namespace]);

        /** @var \ManaPHP\Mongodb\ConnectionInterface $connection */
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
     * @throws \ManaPHP\Mongodb\Exception
     */
    public function bulkUpsert($source, $documents, $primaryKey)
    {
        $namespace = $this->_completeNamespace($source);

        $this->fireEvent('mongodb:bulkWriting', ['namespace' => $namespace]);
        $this->fireEvent('mongodb:bulkUpserting', ['namespace' => $namespace]);

        /** @var \ManaPHP\Mongodb\ConnectionInterface $connection */
        $connection = $this->poolManager->pop($this);
        try {
            $count = $connection->bulkUpsert($namespace, $documents, $primaryKey);
        } finally {
            $this->poolManager->push($this, $connection);
        }
        $this->fireEvent('mongodb:bulkUpserted');
        $this->fireEvent('mongodb:bulkWritten');

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
        $namespace = $this->_completeNamespace($source);

        $this->fireEvent('mongodb:deleting', ['namespace' => $namespace]);

        /** @var \ManaPHP\Mongodb\ConnectionInterface $connection */
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
        $namespace = $this->_completeNamespace($source);

        $this->fireEvent('mongodb:querying', compact('namespace', 'filter', 'options'));

        /** @var \ManaPHP\Mongodb\ConnectionInterface $connection */
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
            $db = $this->_default_db;
        }

        $this->fireEvent('mongodb:commanding', compact('db', 'command'));

        /** @var \ManaPHP\Mongodb\ConnectionInterface $connection */
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

        $collection = $this->_prefix . $collection;
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
                'aggregate' => json_stringify($pipeline),
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

        $collection = $this->_prefix . $collection;
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
        if ($this->_prefix === '') {
            foreach ($result as $collection) {
                $collections[] = $collection['name'];
            }
        } else {
            $prefix = $this->_prefix;
            $prefix_len = strlen($prefix);
            foreach ($result as $collection) {
                $name = $collection['name'];
                if (strpos($name, $prefix) === 0) {
                    $collections[] = substr($name, $prefix_len);
                }
            }
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