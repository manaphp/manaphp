<?php

namespace ManaPHP;

use ManaPHP\Db\AssignmentInterface;
use ManaPHP\Db\Connection;
use ManaPHP\Db\Exception as DbException;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\MisuseException;

/**
 * Class ManaPHP\Db
 *
 * @package db
 */
abstract class Db extends Component implements DbInterface
{
    const METADATA_ATTRIBUTES = 0;
    const METADATA_PRIMARY_KEY = 1;
    const METADATA_AUTO_INCREMENT_KEY = 3;
    const METADATA_INT_TYPE_ATTRIBUTES = 5;

    /**
     * @var string
     */
    protected $_dsn;

    /**
     * @var string
     */
    protected $_username;

    /**
     * @var string
     */
    protected $_password;

    /**
     * @var array
     */
    protected $_options = [];

    /**
     * @var \ManaPHP\Db\Connection
     */
    protected $_connection;

    /**
     * Active SQL Statement
     *
     * @var string
     */
    protected $_sql;

    /**
     * Active SQL bound parameter variables
     *
     * @var array
     */
    protected $_bind = [];

    /**
     * Current transaction level
     *
     * @var int
     */
    protected $_transaction_level = 0;

    /**
     * Last affected rows
     *
     * @var int
     */
    protected $_affected_rows;

    /**
     * @var int
     */
    protected $_timeout = 3;

    /**
     * \ManaPHP\Db\Adapter constructor
     *
     */
    public function __construct()
    {
        $this->_options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;
        $this->_options[\PDO::ATTR_EMULATE_PREPARES] = false;
        $this->_options[\PDO::ATTR_TIMEOUT] = $this->_timeout;
    }

    /**
     * @return \ManaPHP\Db\Connection
     */
    protected function _getConnection()
    {
        if ($this->_connection === null) {
            $this->_connection = new Connection($this->_dsn, $this->_username, $this->_password, $this->_options);
        }

        return $this->_connection;
    }

    protected function _escapeIdentifier($identifier)
    {
        $list = [];
        foreach (explode('.', $identifier) as $id) {
            if ($identifier[0] === '[') {
                $list[] = $id;
            } else {
                $list[] = "[$id]";
            }
        }

        return implode('.', $list);
    }

    /**
     * Sends SQL statements to the database server returning the success state.
     * Use this method only when the SQL statement sent to the server does n't return any rows
     *
     * @param string $type
     * @param string $sql
     * @param array  $bind
     *
     * @return int
     * @throws \ManaPHP\Db\Exception
     */
    public function _execute($type, $sql, $bind = [])
    {
        $this->_sql = $this->replaceQuoteCharacters($sql);
        $this->_bind = $bind;

        $this->_affected_rows = 0;

        $this->eventsManager->fireEvent('db:before' . ucfirst($type), $this);

        $connection = $this->_getConnection();
        $start_time = microtime(true);
        try {
            $this->_affected_rows = $connection->execute($sql, $bind);
        } catch (\PDOException $e) {
            throw new DbException([
                ':message => ' . PHP_EOL . 'SQL: ":sql"' . PHP_EOL . ' BIND: :bind',
                'message' => $e->getMessage(),
                'sql' => $this->_sql,
                'bind' => json_encode($this->_bind, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            ]);
        }

        $count = $this->_affected_rows;
        $elapsed = round(microtime(true) - $start_time, 3);

        $event_data = compact('count', 'sql', 'bind', 'elapsed');
        if (is_int($this->_affected_rows)) {
            $this->eventsManager->fireEvent('db:after' . ucfirst($type), $this, $event_data);
        }

        $this->logger->info($event_data, 'db.' . $type);
        return $count;
    }

    /**
     * Returns the number of affected rows by the last INSERT/UPDATE/DELETE reported by the database system
     *
     * @return int
     */
    public function affectedRows()
    {
        return $this->_affected_rows;
    }

    /**
     * Returns the first row in a SQL query result
     *
     * @param string $sql
     * @param array  $bind
     * @param int    $fetchMode
     * @param bool   $useMaster
     *
     * @return array|false
     * @throws \ManaPHP\Db\Exception
     */
    public function fetchOne($sql, $bind = [], $fetchMode = \PDO::FETCH_ASSOC, $useMaster = false)
    {
        return ($rs = $this->fetchAll($sql, $bind, $fetchMode, $useMaster)) ? $rs[0] : false;
    }

    /**
     * Dumps the complete result of a query into an array
     *
     * @param string $sql
     * @param array  $bind
     * @param int    $fetchMode
     * @param bool   $useMaster
     *
     * @return array
     * @throws \ManaPHP\Db\Exception
     */
    public function fetchAll($sql, $bind = [], $fetchMode = \PDO::FETCH_ASSOC, $useMaster = false)
    {
        $this->_sql = $sql = $this->replaceQuoteCharacters($sql);
        $this->_bind = $bind;
        $this->_affected_rows = 0;

        $this->eventsManager->fireEvent('db:beforeQuery', $this);

        $connection = $this->_getConnection();

        $start_time = microtime(true);
        $result = $connection->query($sql, $bind, $fetchMode, $useMaster);
        $elapsed = round(microtime(true) - $start_time, 3);

        $count = $this->_affected_rows = count($result);

        $event_data = compact('elapsed', 'count', 'sql', 'bind', 'result');

        $this->logger->debug($event_data, 'db.query');

        if (($r = $this->eventsManager->fireEvent('db:afterQuery', $this, $event_data)) !== null) {
            $result = $r;
        }

        return $result;
    }

    /**
     * @param string $table
     * @param array  $record
     * @param string $primaryKey
     *
     * @return int
     * @throws \ManaPHP\Db\Exception
     */
    public function insertOrSkip($table, $record, $primaryKey = null)
    {
        if (!$record) {
            throw new InvalidArgumentException(['Unable to insert into :table table without data', 'table' => $table]);
        }
        $fields = array_keys($record);
        $insertedValues = ':' . implode(',:', $fields);
        $insertedFields = '[' . implode('],[', $fields) . ']';

        $sql = 'INSERT' . ' IGNORE INTO ' . $this->_escapeIdentifier($table) . " ($insertedFields) VALUES ($insertedValues)";

        $count = $this->_execute('insert', $sql, $record);
        $this->logger->info(compact('count', 'table', 'record'), 'db.insert');

        return $count;
    }

    /**
     * @param string $table
     * @param array  $record
     * @param bool   $fetchInsertId
     *
     * @return int|string|null
     * @throws \ManaPHP\Db\Exception
     */
    public function insert($table, $record, $fetchInsertId = false)
    {
        if (!$record) {
            throw new InvalidArgumentException(['Unable to insert into :table table without data', 'table' => $table]);
        }
        $fields = array_keys($record);
        $insertedValues = ':' . implode(',:', $fields);
        $insertedFields = '[' . implode('],[', $fields) . ']';

        $sql = 'INSERT' . ' INTO ' . $this->_escapeIdentifier($table) . " ($insertedFields) VALUES ($insertedValues)";

        $this->_sql = $sql = $this->replaceQuoteCharacters($sql);
        $this->_bind = $bind = $record;

        $this->_affected_rows = 0;

        $connection = $this->_getConnection();

        $this->eventsManager->fireEvent('db:beforeInsert', $this);

        $start_time = microtime(true);
        if ($fetchInsertId) {
            $insert_id = $connection->execute($sql, $record, true);
            $this->_affected_rows = 1;
        } else {
            $connection->execute($sql, $record, false);
            $insert_id = null;
        }

        $elapsed = round(microtime(true) - $start_time, 3);

        $event_data = compact('sql', 'record', 'elapsed');

        $this->eventsManager->fireEvent('db:afterInsert', $this, $event_data);

        $this->logger->info(compact('elapsed', 'insert_id', 'sql', 'bind'), 'db.insert');

        return $insert_id;
    }

    /**
     * @param string  $table
     * @param array[] $records
     * @param null    $primaryKey
     * @param bool    $skipIfExists
     *
     * @return int
     * @throws \ManaPHP\Db\Exception
     */
    public function bulkInsert($table, $records, $primaryKey = null, $skipIfExists = false)
    {
        $count = 0;
        foreach ($records as $record) {
            if ($skipIfExists) {
                $count += $this->insertOrSkip($table, $record, $primaryKey);
            } else {
                $this->insert($table, $record);
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param string $sql
     * @param array  $bind
     *
     * @return int
     */
    public function insertBySql($sql, $bind = [])
    {
        return $this->_execute('insert', $sql, $bind);
    }

    /**
     * Updates data on a table using custom SQL syntax
     *
     * @param    string       $table
     * @param    array        $fieldValues
     * @param    string|array $conditions
     * @param    array        $bind
     *
     * @return    int
     * @throws \ManaPHP\Db\Exception
     */
    public function update($table, $fieldValues, $conditions, $bind = [])
    {
        if (!$fieldValues) {
            throw new InvalidArgumentException(['Unable to update :table table without data', 'table' => $table]);
        }

        if (is_string($conditions)) {
            $conditions = [$conditions];
        }

        $wheres = [];

        /** @noinspection ForeachSourceInspection */
        foreach ($conditions as $k => $v) {
            if (is_int($k)) {
                $wheres[] = stripos($v, ' or ') ? "($v)" : $v;
            } else {
                $wheres[] = "[$k]=:$k";
                $bind[$k] = $v;
            }
        }

        $setFields = [];
        foreach ($fieldValues as $k => $v) {
            if (is_int($k)) {
                $setFields[] = $v;
            } elseif ($v instanceof AssignmentInterface) {
                $v->setFieldName($k);
                $setFields[] = $v->getSql();
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $bind = array_merge($bind, $v->getBind());
            } else {
                $setFields[] = "[$k]=:$k";
                $bind[$k] = $v;
            }
        }

        $sql = 'UPDATE ' . $this->_escapeIdentifier($table) . ' SET ' . implode(',', $setFields) . ' WHERE ' . implode(' AND ', $wheres);

        return $this->_execute('update', $sql, $bind);
    }

    /**
     * Updates data on a table using custom SQL syntax
     *
     * @param   string $sql
     * @param   array  $bind
     *
     * @return    int
     */
    public function updateBySql($sql, $bind = [])
    {
        return $this->_execute('update', $sql, $bind);
    }

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
    public function upsert($table, $insertFieldValues, $updateFieldValues = [], $primaryKey = null)
    {
        if (!$primaryKey) {
            $primaryKey = (string)key($insertFieldValues);
        }

        if ($this->query($table)->whereEq($primaryKey, $insertFieldValues[$primaryKey])->exists()) {
            $bind = [];
            $updates = [];
            foreach ($updateFieldValues as $k => $v) {
                $field = is_string($k) ? $k : $v;
                if ($primaryKey === $field) {
                    continue;
                }

                if (is_int($k)) {
                    $updates[] = "[$field]=:$field";
                    $bind[$field] = $insertFieldValues[$field];
                } elseif ($v instanceof AssignmentInterface) {
                    $v->setFieldName($k);
                    $updates[] = $v->getSql();
                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $bind = array_merge($bind, $v->getBind());
                } else {
                    $updates[] = $v;
                }
            }
            return $this->update($table, $updates, [$primaryKey => $insertFieldValues[$primaryKey]], $bind);
        } else {
            return $this->insert($table, $insertFieldValues);
        }
    }

    /**
     * Deletes data from a table using custom SQL syntax
     *
     * @param  string       $table
     * @param  string|array $conditions
     * @param  array        $bind
     *
     * @return int
     * @throws \ManaPHP\Db\Exception
     */
    public function delete($table, $conditions, $bind = [])
    {
        if (is_string($conditions)) {
            $conditions = [$conditions];
        }

        $wheres = [];
        /** @noinspection ForeachSourceInspection */
        foreach ($conditions as $k => $v) {
            if (is_int($k)) {
                $wheres[] = stripos($v, ' or ') ? "($v)" : $v;
            } else {
                $wheres[] = "[$k]=:$k";
                $bind[$k] = $v;
            }
        }

        $sql = 'DELETE' . ' FROM ' . $this->_escapeIdentifier($table) . ' WHERE ' . implode(' AND ', $wheres);
        return $this->_execute('delete', $sql, $bind);
    }

    /**
     * Deletes data from a table using custom SQL syntax
     *
     * @param  string $sql
     * @param  array  $bind
     *
     * @return int
     */
    public function deleteBySql($sql, $bind = [])
    {
        return $this->_execute('delete', $sql, $bind);
    }

    /**
     * Active SQL statement in the object
     *
     * @return string
     */
    public function getSQL()
    {
        return $this->_sql;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function _quote($value)
    {
        return "'" . str_replace($value, "'", "\\'") . "'";
    }

    /**
     * @param mixed $value
     * @param int   $preservedStrLength
     *
     * @return int|string
     */
    protected function _parseBindValue($value, $preservedStrLength)
    {
        if (is_string($value)) {
            $quoted = $this->_quote($value);
            if ($preservedStrLength > 0 && strlen($quoted) >= $preservedStrLength) {
                return substr($quoted, 0, $preservedStrLength) . '...';
            } else {
                return $quoted;
            }
        } elseif (is_int($value)) {
            return $value;
        } elseif ($value === null) {
            return 'NULL';
        } elseif (is_bool($value)) {
            return (int)$value;
        } else {
            return $value;
        }
    }

    /**
     * Active SQL statement in the object with replace the bind with value
     *
     * @param int $preservedStrLength
     *
     * @return string
     */
    public function getEmulatedSQL($preservedStrLength = -1)
    {
        if (!$this->_bind) {
            return (string)$this->_sql;
        }

        $bind = $this->_bind;
        if (isset($bind[0])) {
            return (string)$this->_sql;
        } else {
            $replaces = [];
            foreach ($bind as $key => $value) {
                $replaces[':' . $key] = $this->_parseBindValue($value, $preservedStrLength);
            }

            return strtr($this->_sql, $replaces);
        }
    }

    /**
     * Active SQL statement in the object
     *
     * @return array
     */
    public function getBind()
    {
        return $this->_bind;
    }

    /**
     * Starts a transaction in the connection
     *
     * @return void
     * @throws \ManaPHP\Db\Exception
     */
    public function begin()
    {
        $this->logger->info('transaction begin', 'db.transaction.begin');

        if ($this->_transaction_level === 0) {
            $this->eventsManager->fireEvent('db:beginTransaction', $this);

            try {
                if (!$this->_getConnection()->beginTransaction()) {
                    throw new DbException('beginTransaction failed.');
                }
            } catch (\PDOException $exception) {
                throw new DbException('beginTransaction failed: ' . $exception->getMessage(), $exception->getCode(), $exception);
            }
        }

        $this->_transaction_level++;
    }

    /**
     * Checks whether the connection is under a transaction
     *
     * @return bool
     */
    public function isUnderTransaction()
    {
        return $this->_transaction_level !== 0;
    }

    /**
     * Rollbacks the active transaction in the connection
     *
     * @return void
     * @throws \ManaPHP\Db\Exception
     */
    public function rollback()
    {
        $this->logger->info('transaction rollback', 'db.transaction.rollback');

        if ($this->_transaction_level === 0) {
            throw new MisuseException('There is no active transaction');
        }

        $this->_transaction_level--;

        if ($this->_transaction_level === 0) {
            $this->eventsManager->fireEvent('db:rollbackTransaction', $this);

            try {
                if (!$this->_connection->rollBack()) {
                    throw new DbException('rollBack failed.');
                }
            } catch (\PDOException $exception) {
                throw new DbException('rollBack failed: ' . $exception->getMessage(), $exception->getCode(), $exception);
            }
        }
    }

    /**
     * Commits the active transaction in the connection
     *
     * @return void
     * @throws \ManaPHP\Db\Exception
     */
    public function commit()
    {
        $this->logger->info('transaction commit', 'db.transaction.commit');

        if ($this->_transaction_level === 0) {
            throw new MisuseException('There is no active transaction');
        }

        $this->_transaction_level--;

        if ($this->_transaction_level === 0) {
            $this->eventsManager->fireEvent('db:commitTransaction', $this);

            try {
                if (!$this->_connection->commit()) {
                    throw new DbException('commit failed.');
                }
            } catch (\PDOException $exception) {
                throw new DbException('commit failed: ' . $exception->getMessage(), $exception->getCode(), $exception);
            }
        }
    }

    /**
     * @return string
     */
    public function getLastSql()
    {
        return $this->_sql;
    }

    public function close()
    {
        if ($this->_connection) {
            if ($this->_transaction_level !== 0) {
                $this->_transaction_level = 0;
                $this->_connection->rollBack();
                $this->logger->error('transaction is not close correctly', 'db.transaction.abnormal');
            }
            $this->_connection = null;
        }
    }

    /**
     * @param string $table
     * @param string $alias
     *
     * @return \ManaPHP\Db\Query
     */
    public function query($table = null, $alias = null)
    {
        return $this->_di->get('ManaPHP\Db\Query', [$this])->from($table, $alias);
    }
}