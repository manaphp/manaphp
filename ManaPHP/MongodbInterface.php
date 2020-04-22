<?php

namespace ManaPHP;

interface MongodbInterface
{
    /**
     * @return string|null
     */
    public function getDefaultDb();

    /**
     * @param string $source
     * @param array  $document
     *
     * @return int
     */
    public function insert($source, $document);

    /**
     * @param string  $source
     * @param array[] $documents
     *
     * @return int
     */
    public function bulkInsert($source, $documents);

    /**
     * @param string $source
     * @param array  $document
     * @param array  $filter
     *
     * @return int
     */
    public function update($source, $document, $filter);

    /**
     * @param string $source
     * @param array  $documents
     * @param string $primaryKey
     *
     * @return int
     */
    public function bulkUpdate($source, $documents, $primaryKey);

    /**
     * @param string $source
     * @param array  $document
     * @param string $primaryKey
     *
     * @return int
     */
    public function upsert($source, $document, $primaryKey);

    /**
     * @param string $source
     * @param array  $documents
     * @param string $primaryKey
     *
     * @return int
     */
    public function bulkUpsert($source, $documents, $primaryKey);

    /**
     * @param string $source
     * @param array  $filter
     *
     * @return int
     */
    public function delete($source, $filter);

    /**
     * @param string   $source
     * @param array    $filter
     * @param array    $options
     * @param bool|int $secondaryPreferred
     *
     * @return array
     */
    public function fetchAll($source, $filter = [], $options = [], $secondaryPreferred = true);

    /**
     * @param array  $command
     * @param string $db
     *
     * @return array[]
     */
    public function command($command, $db = null);

    /**
     * @param string $source
     * @param array  $pipeline
     * @param array  $options
     *
     * @return array
     */
    public function aggregate($source, $pipeline, $options = []);

    /**
     * @param string $source
     *
     * @return bool
     */
    public function truncate($source);

    /**
     * @return array
     */
    public function listDatabases();

    /**
     * @param string $db
     *
     * @return array
     */
    public function listCollections($db = null);

    /**
     * @param string $collection
     *
     * @return \ManaPHP\Mongodb\Query
     */
    public function query($collection = null);
}