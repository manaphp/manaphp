<?php

namespace ManaPHP;

use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
use MongoDB\Driver\ReadPreference;
use MongoDB\Driver\WriteConcern;

class Mongodb extends Component implements MongodbInterface
{
    /**
     * @var string
     */
    protected $_database;

    /**
     * @var \MongoDB\Driver\Manager;
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
     *
     * @throws \MongoDB\Driver\Exception\InvalidArgumentException
     * @throws \MongoDB\Driver\Exception\RuntimeException
     */
    public function __construct($dsn = 'mongodb://127.0.0.1:27017/')
    {
        $this->_manager = new Manager($dsn);
        $pos = strrpos($dsn, '/');
        if ($pos !== false) {
            $this->_database = substr($dsn, $pos + 1);
        }
    }

    /**
     * @param string                    $source
     * @param \MongoDb\Driver\BulkWrite $bulk
     *
     * @return \MongoDB\Driver\WriteResult
     */
    public function bulkWrite($source, $bulk)
    {
        $ns = strpos($source, '.') === false ? ($this->_database . '.' . $source) : $source;

        if ($this->_writeConcern === null) {
            $this->_writeConcern = new \MongoDB\Driver\WriteConcern(WriteConcern::MAJORITY, 1000);
        }

        return $this->_manager->executeBulkWrite($ns, $bulk, $this->_writeConcern);
    }

    /**
     * @param string $source
     * @param array  $document
     *
     * @return \MongoDB\BSON\ObjectID|int|string
     */
    public function insert($source, $document)
    {
        $bulk = new BulkWrite();
        $id = $bulk->insert($document);
        $this->bulkWrite($source, $bulk);
        return $id ?: $document['_id'];
    }

    /**
     * @param string $source
     * @param array  $filter
     * @param array  $document
     * @param array  $updateOptions
     *
     * @return int
     */
    public function update($source, $filter, $document, $updateOptions = [])
    {
        $bulk = new BulkWrite();
        $bulk->update($filter, $document, $updateOptions);
        $result = $this->bulkWrite($source, $bulk);

        return $result->getModifiedCount();
    }

    /**
     * @param string $source
     * @param array  $filter
     * @param array  $deleteOptions
     *
     * @return int|null
     */
    public function delete($source, $filter, $deleteOptions = [])
    {
        $bulk = new BulkWrite();
        $bulk->delete($filter, $deleteOptions);
        $result = $this->bulkWrite($source, $bulk);
        return $result->getDeletedCount();
    }

    /**
     * @param string   $source
     * @param array    $filter
     * @param array    $queryOptions
     * @param bool|int $secondaryPreferred
     *
     * @return array
     */
    public function query($source, $filter = [], $queryOptions = [], $secondaryPreferred = true)
    {
        $ns = strpos($source, '.') === false ? ($this->_database . '.' . $source) : $source;

        if (is_bool($secondaryPreferred)) {
            $readPreference = $secondaryPreferred ? ReadPreference::RP_SECONDARY_PREFERRED : ReadPreference::RP_PRIMARY;
        } else {
            $readPreference = $secondaryPreferred;
        }
        $cursor = $this->_manager->executeQuery($ns, new Query($filter, $queryOptions), new ReadPreference($readPreference));
        return $cursor->toArray();
    }
}