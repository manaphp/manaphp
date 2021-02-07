<?php

namespace ManaPHP\Data\Mongodb;

use ManaPHP\Component;
use ManaPHP\Data\Mongodb\Exception as MongodbException;
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
    protected $uri;

    /**
     * @var \MongoDB\Driver\Manager
     */
    protected $manager;

    /**
     * @var \MongoDB\Driver\WriteConcern
     */
    protected $writeConcern;

    /**
     * @var int
     */
    protected $heartbeat = 60;

    /**
     * @var float
     */
    protected $last_heartbeat;

    /**
     * @param string $uri
     */
    public function __construct($uri)
    {
        $this->uri = $uri;
    }

    /**
     * @return bool
     */
    protected function ping()
    {
        try {
            $command = new Command(['ping' => 1]);
            $this->manager->executeCommand('admin', $command);
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * @return \MongoDB\Driver\Manager
     */
    protected function getManager()
    {
        if ($this->manager === null) {
            $uri = $this->uri;

            $this->fireEvent('mongodb:connect', compact('uri'));
            $this->manager = new Manager($uri);
        }

        if (microtime(true) - $this->last_heartbeat > $this->heartbeat && !$this->ping()) {
            $this->close();
            $this->fireEvent('mongodb:connect', compact('uri'));

            $this->manager = new Manager($this->uri);
        }

        $this->last_heartbeat = microtime(true);

        return $this->manager;
    }

    /**
     * @param string                    $namespace
     * @param \MongoDB\Driver\BulkWrite $bulk
     *
     * @return \MongoDB\Driver\WriteResult
     * @throws \ManaPHP\Data\Mongodb\Exception
     */
    public function bulkWrite($namespace, $bulk)
    {
        if ($this->writeConcern === null) {
            try {
                $this->writeConcern = new WriteConcern(WriteConcern::MAJORITY, 10000);
            } catch (\Exception $exception) {
                throw new MongodbException($exception->getMessage(), $exception->getCode(), $exception);
            }
        }

        $start_time = microtime(true);
        if ($start_time - $this->last_heartbeat > 1.0) {
            $this->last_heartbeat = null;
        }
        try {
            $result = $this->getManager()->executeBulkWrite($namespace, $bulk, $this->writeConcern);
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
     * @throws \ManaPHP\Data\Mongodb\Exception
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
     * @param string $source
     * @param array  $document
     * @param array  $filter
     *
     * @return int
     */
    public function update($source, $document, $filter)
    {
        $bulk = new BulkWrite();

        try {
            $bulk->update($filter, key($document)[0] === '$' ? $document : ['$set' => $document], ['multi' => true]);
        } catch (\Exception $exception) {
            throw new MongodbException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $this->bulkWrite($source, $bulk)->getModifiedCount();
    }

    /**
     * @param string $source
     * @param array  $documents
     * @param string $primaryKey
     *
     * @return int
     */
    public function bulkUpdate($source, $documents, $primaryKey)
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

        return $this->bulkWrite($source, $bulk)->getModifiedCount();
    }

    /**
     * @param string $namespace
     * @param array  $document
     * @param string $primaryKey
     *
     * @return int
     * @throws \ManaPHP\Data\Mongodb\Exception
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
     * @throws \ManaPHP\Data\Mongodb\Exception
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
    protected function fetchAllInternal($namespace, $filter, $options, $readPreference)
    {
        $cursor = $this->getManager()->executeQuery($namespace, new MongodbQuery($filter, $options), $readPreference);
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
            $result = $this->fetchAllInternal($namespace, $filter, $options, $readPreference);
        } catch (\Exception $exception) {
            $result = null;
            $failed = true;
            if (!$this->ping()) {
                try {
                    $this->close();
                    $result = $this->fetchAllInternal($namespace, $filter, $options, $readPreference);
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
        if ($start_time - $this->last_heartbeat > 1.0) {
            $this->last_heartbeat = null;
        }
        try {
            $cursor = $this->getManager()->executeCommand($db, new Command($command));
            $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
            return $cursor->toArray();
        } catch (\Exception $exception) {
            throw new MongodbException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * @return void
     */
    public function close()
    {
        $this->manager = null;
        $this->last_heartbeat = null;
    }
}