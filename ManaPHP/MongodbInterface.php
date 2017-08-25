<?php
namespace ManaPHP;

interface MongodbInterface
{

    /**
     * @param string $source
     * @param array  $document
     *
     * @return \MongoDB\BSON\ObjectID|int|string
     */
    public function insert($source, $document);

    /**
     * @param string $source
     * @param array  $filter
     * @param array  $document
     * @param array  $updateOptions
     *
     * @return int
     */
    public function update($source, $filter, $document, $updateOptions = []);

    /**
     * @param string $source
     * @param array  $filter
     * @param array  $deleteOptions
     *
     * @return int|null
     */
    public function delete($source, $filter, $deleteOptions = []);

    /**
     * @param string   $source
     * @param array    $filter
     * @param array    $queryOptions
     * @param bool|int $secondaryPreferred
     *
     * @return array
     */
    public function query($source, $filter = [], $queryOptions = [], $secondaryPreferred = true);

}