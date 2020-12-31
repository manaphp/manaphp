<?php

namespace ManaPHP\Data\Db;

interface ConnectionInterface
{
    /**
     * @return string
     */
    public function getUri();

    /**
     * @param string $sql
     * @param array  $bind
     * @param bool   $has_insert_id
     *
     * @return int
     * @throws \ManaPHP\Data\Db\ConnectionException
     * @throws \ManaPHP\Exception\InvalidValueException
     * @throws \ManaPHP\Exception\NotSupportedException
     */
    public function execute($sql, $bind = [], $has_insert_id = false);

    /**
     * @param string $sql
     * @param array  $bind
     * @param int    $mode
     *
     * @return array
     */
    public function query($sql, $bind, $mode);

    /**
     * @param string $table
     *
     * @return array
     * @throws \ManaPHP\Data\Db\Exception
     */
    public function getMetadata($table);

    /**
     * @return bool
     */
    public function begin();

    /**
     * @return bool
     */
    public function commit();

    /**
     * @return bool
     */
    public function rollback();

    /**
     * @param string $table
     *
     * @return static
     * @throws \ManaPHP\Data\Db\Exception
     */
    public function truncate($table);

    /**
     * @param string $table
     *
     * @return static
     * @throws \ManaPHP\Data\Db\Exception
     */
    public function drop($table);

    /**
     * @param string $schema
     *
     * @return array
     * @throws \ManaPHP\Data\Db\Exception
     */
    public function getTables($schema = null);

    /**
     * @param string $table
     *
     * @return bool
     * @throws \ManaPHP\Data\Db\Exception
     */
    public function tableExists($table);

    /**
     * @param array $params
     *
     * @return string
     */
    public function buildSql($params);

    /**
     * @param string $sql
     *
     * @return string
     */
    public function replaceQuoteCharacters($sql);
}