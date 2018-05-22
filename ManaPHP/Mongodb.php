<?php

namespace ManaPHP;

use ManaPHP\Mongodb\Exception as MongodbException;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Command;
use MongoDB\Driver\Exception\ConnectionTimeoutException;
use MongoDB\Driver\Exception\RuntimeException;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
use MongoDB\Driver\ReadPreference;
use MongoDB\Driver\WriteConcern;

class Mongodb extends Component implements MongodbInterface
{
    /**
     * @var string
     */
    protected $_dsn;

    /**
     * @var string
     */
    protected $_defaultDb;

    /**
     * @var \MongoDB\Driver\Manager
     */
    protected $_manager;

    /**
     * @var \MongoDB\Driver\WriteConcern
     */
    protected $_writeConcern;

    /**
     * Mongodb constructor.
     *
     * @param string|array $dsn
     */
    public function __construct($dsn = 'mongodb://127.0.0.1:27017/')
    {
        $this->_dsn = $dsn;

        $pos = strrpos($dsn, '/');
        if ($pos !== false) {
            $this->_defaultDb = substr($dsn, $pos + 1);
        }
    }

    /**
     * Pings a server connection, or tries to reconnect if the connection has gone down
     *
     * @return bool
     */
    public function ping()
    {
        for ($i = $this->_manager ? 0 : 1; $i < 2; $i++) {
            try {
                /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                /** @noinspection NullPointerExceptionInspection */
                /** @noinspection PhpUnhandledExceptionInspection */
                $cursor = $this->_getManager()->executeCommand('admin', new Command(['ping' => 1]));
                $cursor->setTypeMap(['root' => 'array']);
                $r = $cursor->toArray()[0];
                if ($r['ok']) {
                    return true;
                }
            } catch (ConnectionTimeoutException $e) {
                $this->_manager = null;
            }
        }

        return false;
    }

    /**
     * @return \MongoDB\Driver\Manager
     */
    protected function _getManager()
    {
        if ($this->_manager === null) {
            $this->fireEvent('mongodb:beforeConnect', ['dsn' => $this->_dsn]);
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $this->_manager = new Manager($this->_dsn);
            $this->fireEvent('mongodb:afterConnect');
        }

        return $this->_manager;
    }

    /**
     * @param string                    $source
     * @param \MongoDb\Driver\BulkWrite $bulk
     *
     * @return \MongoDB\Driver\WriteResult
     * @throws \MongoDB\Driver\Exception\InvalidArgumentException
     */
    public function bulkWrite($source, $bulk)
    {
        $ns = strpos($source, '.') === false ? ($this->_defaultDb . '.' . $source) : $source;

        if ($this->_writeConcern === null) {
            $this->_writeConcern = new WriteConcern(WriteConcern::MAJORITY, 10000);
        }

        return $this->_getManager()->executeBulkWrite($ns, $bulk, $this->_writeConcern);
    }

    /**
     * @param string $source
     * @param array  $document
     *
     * @return \MongoDB\BSON\ObjectID|int|string
     * @throws \MongoDB\Driver\Exception\InvalidArgumentException
     */
    public function insert($source, $document)
    {
        $ns = strpos($source, '.') !== false ? $source : ($this->_defaultDb . '.' . $source);

        $bulk = new BulkWrite();
        $id = $bulk->insert($document);
        $this->fireEvent('mongodb:beforeInsert', ['namespace' => $ns]);
        $this->bulkWrite($ns, $bulk);
        $this->fireEvent('mongodb:afterInsert');

        return $id ?: $document['_id'];
    }

    /**
     * @param string $source
     * @param array  $document
     * @param array  $filter
     * @param array  $updateOptions
     *
     * @return int
     * @throws \MongoDB\Driver\Exception\InvalidArgumentException
     */
    public function update($source, $document, $filter, $updateOptions = [])
    {
        $ns = strpos($source, '.') !== false ? $source : ($this->_defaultDb . '.' . $source);

        $bulk = new BulkWrite();
        $updateOptions += ['multi' => true];

        $bulk->update($filter, ['$set' => $document], $updateOptions);
        $this->fireEvent('mongodb:beforeUpdate', ['namespace' => $ns]);
        $result = $this->bulkWrite($ns, $bulk);
        $this->fireEvent('mongodb:afterUpdate');
        return $result->getModifiedCount();
    }

    /**
     * @param string $source
     * @param array  $filter
     * @param array  $deleteOptions
     *
     * @return int|null
     * @throws \MongoDB\Driver\Exception\InvalidArgumentException
     */
    public function delete($source, $filter, $deleteOptions = [])
    {
        $ns = strpos($source, '.') !== false ? $source : ($this->_defaultDb . '.' . $source);

        $bulk = new BulkWrite();
        $bulk->delete($filter, $deleteOptions);
        $this->fireEvent('mongodb:beforeDelete', ['namespace' => $ns]);
        $result = $this->bulkWrite($ns, $bulk);
        $this->fireEvent('mongodb:afterDelete');
        return $result->getDeletedCount();
    }

    /**
     * @param string   $source
     * @param array    $filter
     * @param array    $queryOptions
     * @param bool|int $secondaryPreferred
     *
     * @return array[]
     */
    public function query($source, $filter = [], $queryOptions = [], $secondaryPreferred = true)
    {
        $ns = strpos($source, '.') !== false ? $source : ($this->_defaultDb . '.' . $source);
        if (is_bool($secondaryPreferred)) {
            $readPreference = $secondaryPreferred ? ReadPreference::RP_SECONDARY_PREFERRED : ReadPreference::RP_PRIMARY;
        } else {
            $readPreference = $secondaryPreferred;
        }
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $this->fireEvent('mongodb:beforeQuery', ['namespace' => $ns]);
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        /** @noinspection PhpUnhandledExceptionInspection */
        $cursor = $this->_getManager()->executeQuery($ns, new Query($filter, $queryOptions), new ReadPreference($readPreference));
        $this->fireEvent('mongodb:afterQuery');
        $cursor->setTypeMap(['root' => 'array']);
        return $cursor->toArray();
    }

    /**
     * @param array  $command
     * @param string $db
     *
     * @return \Mongodb\Driver\Cursor
     */
    public function command($command, $db = null)
    {
        $this->fireEvent('mongodb:beforeExecuteCommand', ['db' => $db ?: $this->_defaultDb, 'command' => $command]);
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        /** @noinspection NullPointerExceptionInspection */
        /** @noinspection PhpUnhandledExceptionInspection */
        $r = $this->_getManager()->executeCommand($db ?: $this->_defaultDb, new Command($command));
        $r->setTypeMap(['root' => 'array', 'document' => 'array']);
        $this->fireEvent('mongodb:afterExecuteCommand');

        return $r;
    }

    /**
     * @param string $source
     * @param array  $pipeline
     * @param array  $options
     *
     * @return array
     * @throws \MongoDB\Driver\Exception\Exception
     * @throws \ManaPHP\Mongodb\Exception
     */
    public function aggregate($source, $pipeline, $options = [])
    {
        $parts = explode('.', $source);

        try {
            $command = ['aggregate' => count($parts) === 2 ? $parts[1] : $parts[0], 'pipeline' => $pipeline];
            if ($options) {
                $command = array_merge($command, $options);
            }
            if (!isset($command['cursor'])) {
                $command['cursor'] = ['batchSize' => 1000];
            }
            $this->fireEvent('mongodb:beforeAggregate', ['namespace' => strpos($source, '.') !== false ? $source : ($this->_defaultDb . '.' . $source)]);
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            /** @noinspection NullPointerExceptionInspection */
            $cursor = $this->_getManager()->executeCommand(count($parts) === 2 ? $parts[0] : $this->_defaultDb, new Command($command));
            $this->fireEvent('mongodb:afterAggregate');
        } catch (RuntimeException $e) {
            throw new MongodbException([
                '`:pipeline` pipeline for `:collection` collection failed: :msg',
                'pipeline' => json_encode($pipeline),
                'collection' => $source,
                'msg' => $e->getMessage()
            ]);
        }
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
        return $cursor->toArray();
    }

    /**
     * @param string $source
     *
     * @return static
     * @throws \ManaPHP\Mongodb\Exception
     */
    public function truncateTable($source)
    {
        $parts = explode('.', $source);
        $db = count($parts) === 2 ? $parts[1] : $this->_defaultDb;
        $collection = count($parts) === 2 ? $parts[1] : $parts[0];
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        try {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            /** @noinspection PhpUnhandledExceptionInspection */
            $cursor = $this->_getManager()->executeCommand($db, new Command(['drop' => $collection]), new ReadPreference(ReadPreference::RP_PRIMARY));
        } /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */ catch (RuntimeException $e) {
            if ($e->getMessage() === 'ns not found') {
                return $this;
            }
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw $e;
        }
        $cursor->setTypeMap(['root' => 'array']);
        $r = $cursor->toArray();
        if (!$r[0]['ok']) {
            throw new MongodbException(['drop `:collection` collection of `:db` db failed: ', 'collection' => $collection, 'db' => $db, 'msg' => $r[0]['errmsg']]);
        }
        return $this;
    }

    /**
     * @return array
     */
    public function listDatabases()
    {
        $databases = [];
        $r = $this->command(['listDatabases' => 1], 'admin')->toArray();
        foreach ((array)$r[0]['databases'] as $database) {
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
        $r = $this->command(['listCollections' => 1], $db)->toArray();
        foreach ($r as $collection) {
            $collections[] = $collection['name'];
        }

        return $collections;
    }

}