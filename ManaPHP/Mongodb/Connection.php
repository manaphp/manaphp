<?php

namespace ManaPHP\Mongodb;

use ManaPHP\Component;
use ManaPHP\Mongodb\Exception as MongodbException;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Command;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query as MongodbQuery;
use MongoDB\Driver\ReadPreference;
use MongoDB\Driver\WriteConcern;

class Connection extends Component implements ConnectionInterface
{
    /**
     * @var string
     */
    protected $_dsn;

    /**
     * @var \MongoDB\Driver\Manager
     */
    protected $_manager;

    /**
     * @var \MongoDB\Driver\WriteConcern
     */
    protected $_writeConcern;

    /**
     * @var int
     */
    protected $_heartbeat = 60;

    /**
     * @var float
     */
    protected $_last_heartbeat;

    /**
     * Connection constructor.
     *
     * @param string $url
     */
    public function __construct($url)
    {
        $this->_dsn = $url;
    }

    /**
     * @return bool
     */
    protected function _ping()
    {
        try {
            $command = new Command(['ping' => 1]);
            $this->_manager->executeCommand('admin', $command);
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * @return \MongoDB\Driver\Manager
     */
    protected function _getManager()
    {
        if ($this->_manager === null) {
            $this->fireEvent('mongodb:connect', ['dsn' => $this->_dsn]);
            $this->_manager = new Manager($this->_dsn);
        }

        if (microtime(true) - $this->_last_heartbeat > $this->_heartbeat && !$this->_ping()) {
            $this->close();
            $this->fireEvent('mongodb:connect', ['dsn' => $this->_dsn]);

            $this->_manager = new Manager($this->_dsn);
        }

        $this->_last_heartbeat = microtime(true);

        return $this->_manager;
    }

    /**
     * @param string                    $namespace
     * @param \MongoDB\Driver\BulkWrite $bulk
     *
     * @return \MongoDB\Driver\WriteResult
     * @throws \ManaPHP\Mongodb\Exception
     */
    public function bulkWrite($namespace, $bulk)
    {
        if ($this->_writeConcern === null) {
            try {
                $this->_writeConcern = new WriteConcern(WriteConcern::MAJORITY, 10000);
            } catch (\Exception $exception) {
                throw new MongodbException($exception->getMessage(), $exception->getCode(), $exception);
            }
        }

        $start_time = microtime(true);
        if ($start_time - $this->_last_heartbeat > 1.0) {
            $this->_last_heartbeat = null;
        }
        try {
            $result = $this->_getManager()->executeBulkWrite($namespace, $bulk, $this->_writeConcern);
        } catch (\Exception $exception) {
            throw new MongodbException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $result;
    }

    /**
     * @param string $namespace
     * @param array  $document
     *
     * @return int
     */
    public function insert($namespace, $document)
    {
        $bulk = new BulkWrite();

        $bulk->insert($document);

        return $this->bulkWrite($namespace, $bulk)->getInsertedCount();
    }

    /**
     * @param string  $namespace
     * @param array[] $documents
     *
     * @return int
     * @throws \ManaPHP\Mongodb\Exception
     */
    public function bulkInsert($namespace, $documents)
    {
        $bulk = new BulkWrite();

        foreach ($documents as $document) {
            $bulk->insert($document);
        }

        return $this->bulkWrite($namespace, $bulk)->getInsertedCount();
    }

    /**
     * @param string $namespace
     * @param array  $document
     * @param array  $filter
     *
     * @return int
     */
    public function update($namespace, $document, $filter)
    {
        $bulk = new BulkWrite();

        try {
            $bulk->update($filter, key($document)[0] === '$' ? $document : ['$set' => $document], ['multi' => true]);
        } catch (\Exception $exception) {
            throw new MongodbException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $this->bulkWrite($namespace, $bulk)->getModifiedCount();
    }

    /**
     * @param string $namespace
     * @param array  $documents
     * @param string $primaryKey
     *
     * @return int
     */
    public function bulkUpdate($namespace, $documents, $primaryKey)
    {
        $bulk = new BulkWrite();

        foreach ($documents as $document) {
            $pkValue = $document[$primaryKey];
            unset($document[$primaryKey]);
            try {
                $bulk->update([$primaryKey => $pkValue], key($document)[0] === '$' ? $document : ['$set' => $document]);
            } catch (\Exception $exception) {
                throw new MongodbException($exception->getMessage(), $exception->getCode(), $exception);
            }
        }

        return $this->bulkWrite($namespace, $bulk)->getModifiedCount();
    }

    /**
     * @param string $namespace
     * @param array  $document
     * @param string $primaryKey
     *
     * @return int
     * @throws \ManaPHP\Mongodb\Exception
     */
    public function upsert($namespace, $document, $primaryKey)
    {
        $bulk = new BulkWrite();

        try {
            $bulk->update([$primaryKey => $document[$primaryKey]], $document, ['upsert' => true]);
        } catch (\Exception $exception) {
            throw new MongodbException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $this->bulkWrite($namespace, $bulk)->getUpsertedCount();
    }

    /**
     * @param string $namespace
     * @param array  $documents
     * @param string $primaryKey
     *
     * @return int
     */
    public function bulkUpsert($namespace, $documents, $primaryKey)
    {
        $bulk = new BulkWrite();

        foreach ($documents as $document) {
            try {
                $bulk->update([$primaryKey => $document[$primaryKey]], $document, ['upsert' => true]);
            } catch (\Exception $exception) {
                throw new MongodbException($exception->getMessage(), $exception->getCode(), $exception);
            }
        }

        return $this->bulkWrite($namespace, $bulk)->getUpsertedCount();
    }

    /**
     * @param string $namespace
     * @param array  $filter
     *
     * @return int
     * @throws \ManaPHP\Mongodb\Exception
     */
    public function delete($namespace, $filter)
    {
        $bulk = new BulkWrite();

        try {
            $bulk->delete($filter);
        } catch (\Exception $exception) {
            throw new MongodbException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $this->bulkWrite($namespace, $bulk)->getDeletedCount();
    }

    /**
     * @param string         $namespace
     * @param array          $filter
     * @param array          $options
     * @param ReadPreference $readPreference
     *
     * @return array
     */
    protected function _fetchAll($namespace, $filter, $options, $readPreference)
    {
        $cursor = $this->_getManager()->executeQuery($namespace, new MongodbQuery($filter, $options), $readPreference);
        $cursor->setTypeMap(['root' => 'array']);
        return $cursor->toArray();
    }

    /**
     * @param string   $namespace
     * @param array    $filter
     * @param array    $options
     * @param bool|int $secondaryPreferred
     *
     * @return array[]
     */
    public function fetchAll($namespace, $filter = [], $options = [], $secondaryPreferred = true)
    {
        if (is_bool($secondaryPreferred)) {
            if ($secondaryPreferred) {
                $readPreference = new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED);
            } else {
                $readPreference = new ReadPreference(ReadPreference::RP_PRIMARY);
            }
        } else {
            $readPreference = new ReadPreference($secondaryPreferred);
        }
        try {
            $result = $this->_fetchAll($namespace, $filter, $options, $readPreference);
        } catch (\Exception $exception) {
            $result = null;
            $failed = true;
            if (!$this->_ping()) {
                try {
                    $this->close();
                    $result = $this->_fetchAll($namespace, $filter, $options, $readPreference);
                    $failed = false;
                } catch (\Exception $exception) {
                }
            }

            if ($failed) {
                throw new MongodbException($exception->getMessage(), $exception->getCode(), $exception);
            }
        }

        return $result;
    }

    /**
     * @param array  $command
     * @param string $db
     *
     * @return array[]
     */
    public function command($command, $db)
    {
        $start_time = microtime(true);
        if ($start_time - $this->_last_heartbeat > 1.0) {
            $this->_last_heartbeat = null;
        }
        try {
            $cursor = $this->_getManager()->executeCommand($db, new Command($command));
            $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
            return $cursor->toArray();
        } catch (\Exception $exception) {
            throw new MongodbException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function close()
    {
        $this->_manager = null;
        $this->_last_heartbeat = null;
    }
}