<?php

namespace ManaPHP;

use PDO;

/**
 * Interface ManaPHP\DbInterface
 *
 * @package db
 */
interface DbInterface
{
    /**
     * @return string
     */
    public function getPrefix();

    /**
     * @param string $type
     * @param string $sql
     * @param array  $bind
     *
     * @return int
     */
    public function execute($type, $sql, $bind = []);

    /**
     * Returns the first row in a SQL query result
     *
     * @param string $sql
     * @param array  $bind
     * @param int    $mode
     * @param bool   $useMaster
     *
     * @return array|false
     */
    public function fetchOne($sql, $bind = [], $mode = PDO::FETCH_ASSOC, $useMaster = false);

    /**
     * Dumps the complete result of a query into an array
     *
     * @param string $sql
     * @param array  $bind
     * @param int    $mode
     * @param bool   $useMaster
     *
     * @return array
     */
    public function fetchAll($sql, $bind = [], $mode = PDO::FETCH_ASSOC, $useMaster = false);

    /**
     * @param string $table
     * @param array  $record
     * @param bool   $fetchInsertId
     *
     * @return int|string|null
     * @throws \ManaPHP\Db\Exception
     */
    public function insert($table, $record, $fetchInsertId = false);

    /**
     * @param string $table
     * @param string $sql
     * @param array  $bind
     *
     * @return int
     */
    public function insertBySql($table, $sql, $bind = []);

    /**
     * Updates data on a table using custom SQL syntax
     *
     * @param string       $table
     * @param array        $fieldValues
     * @param string|array $conditions
     * @param array        $bind
     *
     * @return    int
     */
    public function update($table, $fieldValues, $conditions, $bind = []);

    /**
     * Updates data on a table using custom SQL syntax
     *
     * @param string $table
     * @param string $sql
     * @param array  $bind
     *
     * @return    int
     */
    public function updateBySql($table, $sql, $bind = []);

    /**
     * Updates data on a table using custom SQL syntax
     *
     * @param string $table
     * @param array  $insertFieldValues
     * @param array  $updateFieldValues
     * @param string $primaryKey
     *
     * @return    int
     */
    public function upsert($table, $insertFieldValues, $updateFieldValues = [], $primaryKey = null);

    /**
     * Deletes data from a table using custom SQL syntax
     *
     * @param string       $table
     * @param string|array $conditions
     * @param array        $bind
     *
     * @return int
     */
    public function delete($table, $conditions, $bind = []);

    /**
     * Deletes data from a table using custom SQL syntax
     *
     * @param string $table
     * @param string $sql
     * @param array  $bind
     *
     * @return int
     */
    public function deleteBySql($table, $sql, $bind = []);

    /**
     * Active SQL statement in the object
     *
     * @return string
     */
    public function getSQL();

    /**
     * Active SQL statement in the object with replace the bind with value
     *
     * @param int $preservedStrLength
     *
     * @return string
     */
    public function getEmulatedSQL($preservedStrLength = -1);

    /**
     * Active SQL statement in the object
     *
     * @return array
     */
    public function getBind();

    /**
     * Returns the number of affected rows by the last INSERT/UPDATE/DELETE reported by the database system
     *
     * @return int
     */
    public function affectedRows();

    /**
     * Starts a transaction in the connection
     *
     * @return void
     */
    public function begin();

    /**
     * Checks whether the connection is under a transaction
     *
     * @return bool
     */
    public function isUnderTransaction();

    /**
     * Rollbacks the active transaction in the connection
     *
     * @return void
     */
    public function rollback();

    /**
     * Commits the active transaction in the connection
     *
     * @return void
     */
    public function commit();

    /**
     * @param string
     *
     * @return array
     */
    public function getMetadata($source);

    /**
     * @param string $schema
     *
     * @return array
     */
    public function getTables($schema = null);

    /**
     * @param array $params
     *
     * @return string
     */
    public function buildSql($params);

    /**
     * @return string
     */
    public function getLastSql();

    /**
     * @param string $table
     * @param string $alias
     *
     * @return \ManaPHP\Db\Query
     */
    public function query($table = null, $alias = null);
}