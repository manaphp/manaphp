<?php

namespace ManaPHP;

/**
 * Interface ManaPHP\DbInterface
 *
 * @package db
 */
interface DbInterface
{
    /**
     * Pings a server connection, or tries to reconnect if the connection has gone down
     *
     * @return void
     */
    public function ping();

    /**
     * @return \ManaPHP\DbInterface
     */
    public function getMasterConnection();

    /**
     * @return \ManaPHP\DbInterface
     */
    public function getSlaveConnection();

    /**
     * @param string $sql
     *
     * @return \PDOStatement
     */
    public function prepare($sql);

    /**
     * Returns the first row in a SQL query result
     *
     * <code>
     *  $db->fetchOne('SELECT * FROM city');
     *  $db->fetchOne('SELECT * FROM city WHERE city_id =:city_id',['city_id'=>5]);
     * </code>
     *
     * @param string|\PDOStatement $statement
     * @param array                $bind
     * @param int                  $fetchMode
     *
     * @return array|false
     */
    public function fetchOne($statement, $bind = [], $fetchMode = \PDO::FETCH_ASSOC);

    /**
     * Dumps the complete result of a query into an array
     *
     *  <code>
     *  $db->fetchAll('SELECT * FROM city');
     *  $db->fetchAll('SELECT * FROM city WHERE city_id <:city_id',['city_id'=>5]);
     * </code>
     *
     * @param string|\PDOStatement  $statement
     * @param array                 $bind
     * @param int                   $fetchMode
     * @param string|callable|array $indexBy
     *
     * @return array
     */
    public function fetchAll($statement, $bind = [], $fetchMode = \PDO::FETCH_ASSOC, $indexBy = null);

    /**
     * @param string $table
     * @param array  $record
     * @param string $primaryKey
     * @param bool   $skipIfExists
     *
     * @return int
     */
    public function insert($table, $record, $primaryKey = null, $skipIfExists = false);

    /**
     * @param string  $table
     * @param array[] $records
     * @param string  $primaryKey
     * @param bool    $skipIfExists
     *
     * @return int
     */
    public function bulkInsert($table, $records, $primaryKey = null, $skipIfExists = false);

    /**
     * Updates data on a table using custom SQL syntax
     *
     * @param    string       $table
     * @param    array        $fieldValues
     * @param    string|array $conditions
     * @param   array         $bind
     *
     * @return    int
     */
    public function update($table, $fieldValues, $conditions, $bind = []);

    /**
     * Deletes data from a table using custom SQL syntax
     *
     * @param  string       $table
     * @param  string|array $conditions
     * @param  array        $bind
     *
     * @return int
     */
    public function delete($table, $conditions, $bind = []);

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
     * Sends SQL statements to the database server returning the success state.
     * Use this method only when the SQL statement sent to the server return rows
     *
     * @param string|\PDOStatement $statement
     * @param array                $bind
     * @param int                  $fetchMode
     *
     * @return \PDOStatement
     */
    public function rawQuery($statement, $bind = [], $fetchMode = \PDO::FETCH_ASSOC);

    /**
     * Sends SQL statements to the database server returning the success state.
     * Use this method only when the SQL statement sent to the server don't return any row
     *
     * @param  string|\PDOStatement $statement
     * @param  array                $bind
     *
     * @return int
     */
    public function execute($statement, $bind = []);

    /**
     * Returns the number of affected rows by the last INSERT/UPDATE/DELETE reported by the database system
     *
     * @return int
     */
    public function affectedRows();

    /**
     * Returns insert id for the auto_increment field inserted in the last SQL statement
     *
     * @return int
     */
    public function lastInsertId();

    /**
     * Starts a transaction in the connection
     *
     * @return void
     */
    public function begin();

    /**
     * Checks whether the connection is under a transaction
     *
     *<code>
     *    $connection->begin();
     *    var_dump($connection->isUnderTransaction()); //true
     *</code>
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
     * @param string $source
     *
     * @return static
     */
    public function truncate($source);

    /**
     * @param string $source
     *
     * @return static
     */
    public function drop($source);

    /**
     * @param string $schema
     *
     * @return array
     */
    public function getTables($schema = null);

    /**
     * @param string $source
     *
     * @return bool
     */
    public function tableExists($source);

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

    /**
     * @return string
     */
    public function getLastSql();

    /**
     * @return mixed
     */
    public function close();

    /**
     * @param string $table
     * @param string $alias
     *
     * @return \ManaPHP\Db\Query
     */
    public function query($table = null, $alias = null);
}