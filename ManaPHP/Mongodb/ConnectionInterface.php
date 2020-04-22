<?php

namespace ManaPHP\Mongodb;

interface ConnectionInterface
{
    /**
     * @param string $namespace
     * @param array  $document
     *
     * @return int
     */
    public function insert($namespace, $document);

    /**
     * @param string  $namespace
     * @param array[] $documents
     *
     * @return int
     */
    public function bulkInsert($namespace, $documents);

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
     * @throws \ManaPHP\Mongodb\Exception
     */
    public function bulkUpdate($source, $documents, $primaryKey);

    /**
     * @param string $namespace
     * @param array  $document
     * @param string $primaryKey
     *
     * @return int
     * @throws \ManaPHP\Mongodb\Exception
     */
    public function upsert($namespace, $document, $primaryKey);

    /**
     * @param string $namespace
     * @param array  $documents
     * @param string $primaryKey
     *
     * @return int
     * @throws \ManaPHP\Mongodb\Exception
     */
    public function bulkUpsert($namespace, $documents, $primaryKey);

    /**
     * @param string $namespace
     * @param array  $filter
     *
     * @return int
     * @throws \ManaPHP\Mongodb\Exception
     */
    public function delete($namespace, $filter);

    /**
     * @param string   $namespace
     * @param array    $filter
     * @param array    $options
     * @param bool|int $secondaryPreferred
     *
     * @return array[]
     */
    public function fetchAll($namespace, $filter = [], $options = [], $secondaryPreferred = true);

    /**
     * @param array  $command
     * @param string $db
     *
     * @return array[]
     */
    public function command($command, $db);
}