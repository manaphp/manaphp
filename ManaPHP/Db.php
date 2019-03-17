<?php

namespace ManaPHP;

use ManaPHP\Db\AssignmentInterface;
use ManaPHP\Db\ConnectionException;
use ManaPHP\Db\Exception as DbException;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;

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
     * @var float
     */
    protected $_ping_interval = 10.0;

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
    protected $_transactionLevel = 0;

    /**
     * @var \PDO
     */
    protected $_pdo;

    /**
     * Last affected rows
     *
     * @var int
     */
    protected $_affectedRows;

    /**
     * @var int
     */
    protected $_timeout = 3;

    /**
     * @var \PDOStatement[]
     */
    protected $_prepared = [];

    /**
     * @var float
     */
    protected $_last_io_time;

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
     * @return bool
     */
    protected function _ping()
    {
        try {
            @$this->_pdo->query("SELECT 'PING'")->fetchAll();
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * @return \PDO
     */
    protected function _getPdo()
    {
        if ($this->_pdo === null) {
            $this->logger->debug(['connect to `:dsn`', 'dsn' => $this->_dsn], 'db.connect');
            $this->eventsManager->fireEvent('db:beforeConnect', $this, ['dsn' => $this->_dsn]);
            try {
                $this->_pdo = @new \PDO($this->_dsn, $this->_username, $this->_password, $this->_options);
            } catch (\PDOException $e) {
                throw new ConnectionException(['connect `:dsn` failed: :message', 'message' => $e->getMessage(), 'dsn' => $this->_dsn], $e->getCode(), $e);
            }
            $this->eventsManager->fireEvent('db:afterConnect', $this);

            if (!isset($this->_options[\PDO::ATTR_PERSISTENT]) || !$this->_options[\PDO::ATTR_PERSISTENT]) {
                $this->_last_io_time = microtime(true);
                return $this->_pdo;
            }
        }

        if ($this->_transactionLevel === 0 && microtime(true) - $this->_last_io_time >= $this->_ping_interval && !$this->_ping()) {
            $this->close();
            $this->logger->info(['reconnect to `:dsn`', 'dsn' => $this->_dsn], 'db.reconnect');
            $this->eventsManager->fireEvent('db:reconnect', $this, ['dsn' => $this->_dsn]);
            $this->eventsManager->fireEvent('db:beforeConnect', $this, ['dsn' => $this->_dsn]);
            try {
                $this->_pdo = @new \PDO($this->_dsn, $this->_username, $this->_password, $this->_options);
            } catch (\PDOException $e) {
                throw new ConnectionException(['connect `:dsn` failed: :message', 'message' => $e->getMessage(), 'dsn' => $this->_dsn], $e->getCode(), $e);
            }
            $this->eventsManager->fireEvent('db:afterConnect', $this);
        }

        $this->_last_io_time = microtime(true);

        return $this->_pdo;
    }

    /**
     * @return \ManaPHP\DbInterface
     */
    public function getMasterConnection()
    {
        return $this;
    }

    /**
     * @return \ManaPHP\DbInterface
     */
    public function getSlaveConnection()
    {
        return $this;
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
     * @param string|\PDOStatement $statement
     * @param array                $bind
     *
     * @return \PDOStatement
     */
    protected function _execute($statement, $bind)
    {
        if (is_string($statement)) {
            if (!isset($this->_prepared[$statement])) {
                if (count($this->_prepared) > 8) {
                    array_shift($this->_prepared);
                }
                $this->_prepared[$statement] = @$this->_getPdo()->prepare($this->replaceQuoteCharacters($statement));
            }
            $statement = $this->_prepared[$statement];
        }

        foreach ($bind as $parameter => $value) {
            if (is_string($value)) {
                $type = \PDO::PARAM_STR;
            } elseif (is_int($value)) {
                $type = \PDO::PARAM_INT;
            } elseif (is_bool($value)) {
                $type = \PDO::PARAM_BOOL;
            } elseif ($value === null) {
                $type = \PDO::PARAM_NULL;
            } elseif (is_float($value)) {
                $type = \PDO::PARAM_STR;
            } elseif (is_array($value) || $value instanceof \JsonSerializable) {
                $type = \PDO::PARAM_STR;
                $value = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } else {
                throw new NotSupportedException(['The `:type` type of `:parameter` parameter is not support', 'parameter' => $parameter, 'type' => gettype($value)]);
            }

            if (is_int($parameter)) {
                $statement->bindValue($parameter + 1, $value, $type);
            } else {
                if ($parameter[0] === ':') {
                    throw new InvalidValueException(['Bind does not require started with `:` for `:parameter` parameter', 'parameter' => $parameter]);
                }

                $statement->bindValue(':' . $parameter, $value, $type);
            }
        }

        @$statement->execute();

        return $statement;
    }

    /**
     * @param string|\PDOStatement $statement
     * @param array                $bind
     * @param int                  $fetchMode
     *
     * @return array
     * @throws \ManaPHP\Db\Exception
     */
    protected function _fetchAll($statement, $bind, $fetchMode)
    {
        $this->_sql = $sql = is_string($statement) ? $this->replaceQuoteCharacters($statement) : $statement->queryString;
        $this->_bind = $bind;
        $this->_affectedRows = 0;

        $this->eventsManager->fireEvent('db:beforeQuery', $this);
        $start_time = microtime(true);
        try {
            $r = $bind ? $this->_execute(is_string($statement) ? $this->_sql : $statement, $bind) : @$this->_getPdo()->query($this->_sql);
        } catch (\PDOException $e) {
            $r = null;
            $failed = true;
            if ($this->_transactionLevel === 0 && !$this->_ping()) {
                try {
                    $this->close();
                    $r = $bind ? $this->_execute($this->_sql, $bind) : @$this->_getPdo()->query($this->_sql);
                    $failed = false;
                } catch (\PDOException $e) {
                }
            }
            if ($failed) {
                throw new DbException([
                    ':message => ' . PHP_EOL . 'SQL: ":sql"' . PHP_EOL . ' BIND: :bind',
                    'message' => $e->getMessage(),
                    'sql' => $this->_sql,
                    'bind' => json_encode($this->_bind, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                ], 0, $e);
            }
        }

        $count = $this->_affectedRows = $r->rowCount();
        $result = $r->fetchAll($fetchMode);
        $elapsed = round(microtime(true) - $start_time, 3);

        $event_data = compact('count', 'sql', 'bind', 'elapsed', 'result');
        $this->eventsManager->fireEvent('db:afterQuery', $this, $event_data);
        $this->logger->debug($event_data, 'db.query');

        return $result;
    }

    /**
     * Sends SQL statements to the database server returning the success state.
     * Use this method only when the SQL statement sent to the server does n't return any rows
     *
     * @param string|\PDOStatement $statement
     * @param array                $bind
     *
     * @return int
     * @throws \ManaPHP\Db\Exception
     */
    public function execute($statement, $bind = [])
    {
        $this->_sql = $sql = is_string($statement) ? $this->replaceQuoteCharacters($statement) : $statement->queryString;
        $this->_bind = $bind;

        if (microtime(true) - $this->_last_io_time > 1.0) {
            $this->_last_io_time = null;
        }

        $this->_affectedRows = 0;

        $this->eventsManager->fireEvent('db:beforeExecute', $this);
        $start_time = microtime(true);
        try {
            $this->_affectedRows = $bind
                ? $this->_execute(is_string($statement) ? $this->_sql : $statement, $bind)->rowCount()
                : @$this->_getPdo()->exec($this->_sql);
        } catch (\PDOException $e) {
            throw new DbException([
                ':message => ' . PHP_EOL . 'SQL: ":sql"' . PHP_EOL . ' BIND: :bind',
                'message' => $e->getMessage(),
                'sql' => $this->_sql,
                'bind' => json_encode($this->_bind, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            ]);
        }

        $count = $this->_affectedRows;
        $elapsed = round(microtime(true) - $start_time, 3);

        $event_data = compact('count', 'sql', 'bind', 'elapsed');
        if (is_int($this->_affectedRows)) {
            $this->eventsManager->fireEvent('db:afterExecute', $this, $event_data);
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
        if (!isset($backtrace['function']) || !in_array($backtrace['function'], ['insert', 'delete', 'update', 'upsert'], true)) {
            $this->logger->info($event_data, 'db.execute');
        }
        return $count;
    }

    /**
     * Returns the number of affected rows by the last INSERT/UPDATE/DELETE reported by the database system
     *
     * @return int
     */
    public function affectedRows()
    {
        return $this->_affectedRows;
    }

    /**
     * Returns the first row in a SQL query result
     *
     * @param string|\PDOStatement $statement
     * @param array                $bind
     * @param int                  $fetchMode
     *
     * @return array|false
     * @throws \ManaPHP\Db\Exception
     */
    public function fetchOne($statement, $bind = [], $fetchMode = \PDO::FETCH_ASSOC)
    {
        return ($rs = $this->_fetchAll($statement, $bind, $fetchMode)) ? $rs[0] : false;
    }

    /**
     * Dumps the complete result of a query into an array
     *
     * @param string|\PDOStatement  $statement
     * @param array                 $bind
     * @param int                   $fetchMode
     * @param string|callable|array $indexBy
     *
     * @return array
     * @throws \ManaPHP\Db\Exception
     */
    public function fetchAll($statement, $bind = [], $fetchMode = \PDO::FETCH_ASSOC, $indexBy = null)
    {
        $rs = $this->_fetchAll($statement, $bind, $fetchMode);

        if ($indexBy === null) {
            return $rs;
        }

        $rows = [];
        if (is_scalar($indexBy)) {
            foreach ($rs as $row) {
                $rows[$row[$indexBy]] = $row;
            }
        } elseif (is_array($indexBy)) {
            $k = key($indexBy);
            $v = current($indexBy);
            foreach ($rs as $row) {
                $rows[$row[$k]] = $row[$v];
            }
        } else {
            foreach ($rs as $row) {
                $rows[$indexBy($row)] = $row;
            }
        }

        return $rows;
    }

    /**
     * @param string $table
     * @param array  $record
     * @param string $primaryKey
     * @param bool   $skipIfExists
     *
     * @return int
     * @throws \ManaPHP\Db\Exception
     */
    public function insert($table, $record, $primaryKey = null, $skipIfExists = false)
    {
        if (!$record) {
            throw new InvalidArgumentException(['Unable to insert into :table table without data', 'table' => $table]);
        }
        $fields = array_keys($record);
        $insertedValues = ':' . implode(',:', $fields);
        $insertedFields = '[' . implode('],[', $fields) . ']';

        $sql = 'INSERT' . ($skipIfExists ? ' IGNORE' : '') . ' INTO ' . $this->_escapeIdentifier($table) . " ($insertedFields) VALUES ($insertedValues)";

        $count = $this->execute($sql, $record);
        $this->logger->info(compact('count', 'table', 'record', 'skipIfExists'), 'db.insert');

        return $count;
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
            $count += $this->insert($table, $record, $primaryKey, $skipIfExists);
        }

        return $count;
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

        $count = $this->execute($sql, $bind);
        $this->logger->info(compact('count', 'table', 'fieldValues', 'conditions', 'bind'), 'db.update');
        return $count;
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
            return $this->insert($table, $insertFieldValues, $primaryKey);
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

        $sql = /**@lang Text */
            'DELETE FROM ' . $this->_escapeIdentifier($table) . ' WHERE ' . implode(' AND ', $wheres);
        $count = $this->execute($sql, $bind);
        $this->logger->info(compact('count', 'table', 'conditions', 'bind'), 'db.delete');
        return $count;
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
     * @param mixed $value
     * @param int   $preservedStrLength
     *
     * @return int|string
     */
    protected function _parseBindValue($value, $preservedStrLength)
    {
        if (is_string($value)) {
            if ($preservedStrLength > 0 && strlen($value) >= $preservedStrLength) {
                return $this->_getPdo()->quote(substr($value, 0, $preservedStrLength) . '...');
            } else {
                return $this->_getPdo()->quote($value);
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

        if ($this->_transactionLevel === 0) {
            if (microtime(true) - $this->_last_io_time > 1.0) {
                $this->_last_io_time = null;
            }

            $this->eventsManager->fireEvent('db:beginTransaction', $this);

            try {
                if (!$this->_getPdo()->beginTransaction()) {
                    throw new DbException('beginTransaction failed.');
                }
            } catch (\PDOException $exception) {
                throw new DbException('beginTransaction failed: ' . $exception->getMessage(), $exception->getCode(), $exception);
            }
        }

        $this->_transactionLevel++;
    }

    /**
     * Checks whether the connection is under a transaction
     *
     * @return bool
     */
    public function isUnderTransaction()
    {
        return $this->_transactionLevel !== 0;
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

        if ($this->_transactionLevel === 0) {
            throw new MisuseException('There is no active transaction');
        }

        $this->_transactionLevel--;

        if ($this->_transactionLevel === 0) {
            $this->eventsManager->fireEvent('db:rollbackTransaction', $this);

            try {
                if (!$this->_pdo->rollBack()) {
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

        if ($this->_transactionLevel === 0) {
            throw new MisuseException('There is no active transaction');
        }

        $this->_transactionLevel--;

        if ($this->_transactionLevel === 0) {
            $this->eventsManager->fireEvent('db:commitTransaction', $this);

            try {
                if (!$this->_pdo->commit()) {
                    throw new DbException('commit failed.');
                }
            } catch (\PDOException $exception) {
                throw new DbException('commit failed: ' . $exception->getMessage(), $exception->getCode(), $exception);
            }
        }
    }

    /**
     * Returns insert id for the auto_increment field inserted in the last SQL statement
     *
     * @return int
     */
    public function lastInsertId()
    {
        return (int)$this->_pdo->lastInsertId();
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
        if ($this->_pdo) {
            $this->_pdo = null;
            $this->_prepared = [];
            $this->_last_io_time = null;
            if ($this->_transactionLevel !== 0) {
                $this->_transactionLevel = 0;
                $this->_pdo->rollBack();
                $this->logger->warn('transaction is not close correctly', 'db.transaction.abnormal');
            }
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