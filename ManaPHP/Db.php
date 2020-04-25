<?php

namespace ManaPHP;

use ManaPHP\Coroutine\Context\Inseparable;
use ManaPHP\Db\Exception as DbException;
use ManaPHP\Db\SqlFragmentable;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use PDO;
use PDOException;

class DbContext implements Inseparable
{
    /**
     * @var \ManaPHP\Db\ConnectionInterface
     */
    public $connection;

    /**
     * Active SQL Statement
     *
     * @var string
     */
    public $sql;

    /**
     * Active SQL bound parameter variables
     *
     * @var array
     */
    public $bind = [];

    /**
     * Current transaction level
     *
     * @var int
     */
    public $transaction_level = 0;

    /**
     * Last affected rows
     *
     * @var int
     */
    public $affected_rows;

    public function __destruct()
    {
        if ($this->transaction_level !== 0) {
            throw new MisuseException('transaction is not close correctly');
        }

        if ($this->connection !== null) {
            throw new MisuseException('connection is not released to pool');
        }
    }
}

/**
 * Class ManaPHP\Db
 *
 * @package db
 * @property-read \ManaPHP\DbContext $_context
 */
class Db extends Component implements DbInterface
{
    const METADATA_ATTRIBUTES = 0;
    const METADATA_PRIMARY_KEY = 1;
    const METADATA_AUTO_INCREMENT_KEY = 3;
    const METADATA_INT_TYPE_ATTRIBUTES = 5;

    /**
     * @var string
     */
    protected $_url;

    /**
     * @var string
     */
    protected $_prefix = '';

    /**
     * @var bool
     */
    protected $_has_slave = false;

    /**
     * @var float
     */
    protected $_timeout = 3.0;

    /**
     * @var int
     */
    protected $_pool_size = 4;

    /**
     * Db constructor.
     *
     * @param string $uri
     */
    public function __construct($uri)
    {
        $this->_url = $uri;

        if (strpos($uri, 'timeout=') !== false && preg_match('#timeout=([\d.]+)#', $uri, $matches) === 1) {
            $this->_timeout = (float)$matches[1];
        }

        if (preg_match('#pool_size=(\d+)#', $uri, $matches)) {
            $this->_pool_size = (int)$matches[1];
        }

        if (preg_match('#[?&]prefix=(\w+)#', $uri, $matches)) {
            $this->_prefix = $matches[1];
        }

        if (strpos($uri, ',') !== false) {
            $hosts = parse_url($uri, PHP_URL_HOST);
            if (strpos($hosts, ',') === false) {
                $urls = explode(',', $uri);
            } else {
                $urls = [];
                foreach (explode(',', $hosts) as $host) {
                    $urls[] = str_replace($hosts, $host, $uri);
                }
            }

            if ($urls[0]) {
                $url = $urls[0];
                $adapter = 'ManaPHP\Db\Connection\Adapter\\' . ucfirst(parse_url($url, PHP_URL_SCHEME));
                $this->poolManager->add($this, ['class' => $adapter, $url], $this->_pool_size);
            }
            array_shift($urls);

            foreach ($urls as $i => $url) {
                if (preg_match('#[?&]readonly\b#', $url) !== 1) {
                    $urls[$i] .= (strpos($url, '?') ? '&' : '?') . 'readonly=1';
                }
            }

            if (MANAPHP_COROUTINE_ENABLED) {
                shuffle($urls);

                $this->poolManager->create($this, count($urls) * $this->_pool_size, 'slave');
                for ($i = 0; $i < $this->_pool_size; $i++) {
                    foreach ($urls as $url) {
                        $adapter = 'ManaPHP\Db\Connection\Adapter\\' . ucfirst(parse_url($url, PHP_URL_SCHEME));
                        $this->poolManager->add($this, ['class' => $adapter, $url], 1, 'slave');
                    }
                }
            } else {
                $url = $urls[random_int(0, count($urls) - 1)];
                $adapter = 'ManaPHP\Db\Connection\Adapter\\' . ucfirst(parse_url($url, PHP_URL_SCHEME));
                $this->poolManager->add($this, ['class' => $adapter, $url], 1, 'slave');
            }

            $this->_has_slave = true;
        } else {
            $adapter = 'ManaPHP\Db\Connection\Adapter\\' . ucfirst(parse_url($uri, PHP_URL_SCHEME));
            $this->poolManager->add($this, ['class' => $adapter, $uri], $this->_pool_size);
        }
    }

    public function __destruct()
    {
        $this->poolManager->remove($this);
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->_prefix;
    }

    /**
     * @param string $type
     * @param string $sql
     * @param array  $bind
     *
     * @return int
     * @throws \ManaPHP\Db\Exception
     */
    public function execute($type, $sql, $bind = [])
    {
        $event = [
                'delete' => ['deleting', 'deleted'],
                'update' => ['updating', 'updated'],
                'insert' => ['inserting', 'inserted']
            ][$type] ?? null;

        $context = $this->_context;

        $context->sql = $sql;
        $context->bind = $bind;

        $context->affected_rows = 0;

        $this->fireEvent('db:executing');
        $event && $this->fireEvent('db:' . $event[0]);

        if ($context->connection) {
            $connection = $context->connection;
        } else {
            $connection = $this->poolManager->pop($this, $this->_timeout);
        }

        try {
            $start_time = microtime(true);
            $context->affected_rows = $connection->execute($sql, $bind);
            $elapsed = round(microtime(true) - $start_time, 3);
        } finally {
            if (!$context->connection) {
                $this->poolManager->push($this, $connection);
            }
        }

        $count = $context->affected_rows;
        $event_data = compact('type', 'count', 'sql', 'bind', 'elapsed');

        $event && $this->fireEvent('db:' . $event[1], $event_data);
        $this->fireEvent('db:executed', $event_data);

        return $count;
    }

    /**
     * Returns the number of affected rows by the last INSERT/UPDATE/DELETE reported by the database system
     *
     * @return int
     */
    public function affectedRows()
    {
        return $this->_context->affected_rows;
    }

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
    public function fetchOne($sql, $bind = [], $mode = PDO::FETCH_ASSOC, $useMaster = false)
    {
        return ($rs = $this->fetchAll($sql, $bind, $mode, $useMaster)) ? $rs[0] : false;
    }

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
    public function fetchAll($sql, $bind = [], $mode = PDO::FETCH_ASSOC, $useMaster = false)
    {
        $context = $this->_context;

        $context->sql = $sql;
        $context->bind = $bind;
        $context->affected_rows = 0;

        $this->fireEvent('db:querying');

        if ($context->connection) {
            $type = null;
            $connection = $context->connection;
        } else {
            $type = $this->_has_slave ? 'slave' : 'default';
            $connection = $this->poolManager->pop($this, $this->_timeout, $type);
        }

        $sql = $connection->replaceQuoteCharacters($sql);
        try {
            $start_time = microtime(true);
            $result = $connection->query($sql, $bind, $mode, $useMaster);
            $elapsed = round(microtime(true) - $start_time, 3);
        } finally {
            if ($type) {
                $this->poolManager->push($this, $connection, $type);
            }
        }

        $count = $context->affected_rows = count($result);

        $this->fireEvent('db:queried', compact('elapsed', 'count', 'sql', 'bind', 'result'));

        return $result;
    }

    /**
     * @param string $table
     *
     * @return string
     */
    protected function _completeTable($table)
    {
        if (($pos = strpos($table, '.')) === false) {
            return '[' . $table . ']';
        } else {
            return '[' . substr($table, 0, $pos) . '].[' . substr($table, $pos + 1) . ']';
        }
    }

    /**
     * @param string $table
     * @param array  $record
     * @param bool   $fetchInsertId
     *
     * @return int|string|null
     */
    public function insert($table, $record, $fetchInsertId = false)
    {
        $context = $this->_context;

        $table = $this->_completeTable($table);

        if (!$record) {
            throw new InvalidArgumentException(['Unable to insert into :table table without data', 'table' => $table]);
        }
        $fields = array_keys($record);
        $insertedValues = ':' . implode(',:', $fields);
        $insertedFields = '[' . implode('],[', $fields) . ']';

        $context->sql = $sql
            = /** @lang text */
            "INSERT INTO $table ($insertedFields) VALUES ($insertedValues)";

        $context->bind = $bind = $record;

        $context->affected_rows = 0;

        $connection = $context->connection ?: $this->poolManager->pop($this, $this->_timeout);

        $this->fireEvent('db:inserting');

        try {
            $start_time = microtime(true);
            if ($fetchInsertId) {
                $insert_id = $connection->execute($sql, $record, true);
                $context->affected_rows = 1;
            } else {
                $connection->execute($sql, $record);
                $insert_id = null;
            }
            $elapsed = round(microtime(true) - $start_time, 3);
        } finally {
            if (!$context->connection) {
                $this->poolManager->push($this, $connection);
            }
        }

        $event_data = compact('sql', 'record', 'elapsed', 'insert_id', 'bind');

        $this->fireEvent('db:inserted', $event_data);

        return $insert_id;
    }

    /**
     * @param string $table
     * @param string $sql
     * @param array  $bind
     *
     * @return int
     */
    public function insertBySql($table, $sql, $bind = [])
    {
        $table = $this->_completeTable($table);

        return $this->execute('insert', /**@lang text */ "INSERT INTO $table $sql", $bind);
    }

    /**
     * Updates data on a table using custom SQL syntax
     *
     * @param string       $table
     * @param array        $fieldValues
     * @param string|array $conditions
     * @param array        $bind
     *
     * @return    int
     * @throws \ManaPHP\Db\Exception
     */
    public function update($table, $fieldValues, $conditions, $bind = [])
    {
        $table = $this->_completeTable($table);

        if (!$fieldValues) {
            throw new InvalidArgumentException(['Unable to update :table table without data', 'table' => $table]);
        }

        if (!$conditions) {
            throw new NotSupportedException(['update must with a condition!']);
        }

        $wheres = [];

        foreach ((array)$conditions as $k => $v) {
            if (is_int($k)) {
                if (!is_string($v) || $v === '' || preg_match('#^\w+$#', $v) === 1) {
                    throw new NotSupportedException(['update with `:condition` condition is danger!', 'condition' => $v]);
                }
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
            } elseif ($v instanceof SqlFragmentable) {
                $v->setField($k);
                $setFields[] = $v->getSql();
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $bind = array_merge($bind, $v->getBind());
            } else {
                $setFields[] = "[$k]=:$k";
                $bind[$k] = $v;
            }
        }

        $sql
            = /**@lang text */
            "UPDATE $table SET " . implode(',', $setFields) . ' WHERE ' . implode(' AND ', $wheres);

        return $this->execute('update', $sql, $bind);
    }

    /**
     * Updates data on a table using custom SQL syntax
     *
     * @param string $table
     * @param string $sql
     * @param array  $bind
     *
     * @return    int
     */
    public function updateBySql($table, $sql, $bind = [])
    {
        $table = $this->_completeTable($table);

        return $this->execute('update', /** @lang text */ "UPDATE $table SET $sql", $bind);
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
                } elseif ($v instanceof SqlFragmentable) {
                    $v->setField($k);
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
     * @param string       $table
     * @param string|array $conditions
     * @param array        $bind
     *
     * @return int
     * @throws \ManaPHP\Db\Exception
     */
    public function delete($table, $conditions, $bind = [])
    {
        $table = $this->_completeTable($table);

        if (!$conditions) {
            throw new NotSupportedException(['delete must with a condition!']);
        }

        $wheres = [];
        foreach ((array)$conditions as $k => $v) {
            if (is_int($k)) {
                if (!is_string($v) || $v === '' || ($v !== 'FALSE' && preg_match('#^\w+$#', $v) === 1)) {
                    throw new NotSupportedException(['delete with `:condition` condition is danger!', 'condition' => $v]);
                }
                $wheres[] = stripos($v, ' or ') ? "($v)" : $v;
            } else {
                $wheres[] = "[$k]=:$k";
                $bind[$k] = $v;
            }
        }

        $sql
            = /** @lang text */
            "DELETE FROM $table WHERE " . implode(' AND ', $wheres);
        return $this->execute('delete', $sql, $bind);
    }

    /**
     * Deletes data from a table using custom SQL syntax
     *
     * @param string $table
     * @param string $sql
     * @param array  $bind
     *
     * @return int
     */
    public function deleteBySql($table, $sql, $bind = [])
    {
        $table = $this->_completeTable($table);

        return $this->execute('delete', /**@lang text */ "DELETE FROM $table WHERE $sql", $bind);
    }

    /**
     * Active SQL statement in the object
     *
     * @return string
     */
    public function getSQL()
    {
        return $this->_context->sql;
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
        $context = $this->_context;

        if (!$context->bind) {
            return (string)$context->sql;
        }

        $bind = $context->bind;
        if (isset($bind[0])) {
            return (string)$context->sql;
        } else {
            $replaces = [];
            foreach ($bind as $key => $value) {
                $replaces[':' . $key] = $this->_parseBindValue($value, $preservedStrLength);
            }

            return strtr($context->sql, $replaces);
        }
    }

    /**
     * Active SQL statement in the object
     *
     * @return array
     */
    public function getBind()
    {
        return $this->_context->bind;
    }

    /**
     * Starts a transaction in the connection
     *
     * @return void
     * @throws \ManaPHP\Db\Exception
     */
    public function begin()
    {
        $context = $this->_context;

        if ($context->transaction_level === 0) {
            $this->fireEvent('db:begin');

            /** @var \ManaPHP\Db\ConnectionInterface $connection */
            $connection = $this->poolManager->pop($this, $this->_timeout);

            try {
                if (!$connection->begin()) {
                    throw new DbException('beginTransaction failed.');
                }
                $context->connection = $connection;
                $context->transaction_level++;
            } catch (PDOException $exception) {
                throw new DbException('beginTransaction failed: ' . $exception->getMessage(), $exception->getCode(), $exception);
            } finally {
                if (!$context->connection) {
                    $this->poolManager->push($this, $connection);
                }
            }
        } else {
            $context->transaction_level++;
        }
    }

    /**
     * Checks whether the connection is under a transaction
     *
     * @return bool
     */
    public function isUnderTransaction()
    {
        $context = $this->_context;

        return $context->transaction_level !== 0;
    }

    /**
     * Rollbacks the active transaction in the connection
     *
     * @return void
     * @throws \ManaPHP\Db\Exception
     */
    public function rollback()
    {
        $context = $this->_context;

        if ($context->transaction_level > 0) {
            $context->transaction_level--;

            if ($context->transaction_level === 0) {
                try {
                    if (!$context->connection->rollback()) {
                        throw new DbException('rollBack failed.');
                    }
                } catch (PDOException $exception) {
                    throw new DbException('rollBack failed: ' . $exception->getMessage(), $exception->getCode(), $exception);
                } finally {
                    $this->poolManager->push($this, $context->connection);
                    $context->connection = null;

                    $this->fireEvent('db:rollback');
                }
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
        $context = $this->_context;

        if ($context->transaction_level === 0) {
            throw new MisuseException('There is no active transaction');
        }

        $context->transaction_level--;

        if ($context->transaction_level === 0) {
            try {
                if (!$context->connection->commit()) {
                    throw new DbException('commit failed.');
                }
            } catch (PDOException $exception) {
                throw new DbException('commit failed: ' . $exception->getMessage(), $exception->getCode(), $exception);
            } finally {
                $this->poolManager->push($this, $context->connection);
                $context->connection = null;
                $this->fireEvent('db:commit');
            }
        }
    }

    /**
     * @return string
     */
    public function getLastSql()
    {
        return $this->_context->sql;
    }

    /**
     * @param string $schema
     *
     * @return array
     * @throws \ManaPHP\Db\Exception
     */
    public function getTables($schema = null)
    {
        $context = $this->_context;

        if ($context->connection) {
            $type = null;
            $connection = $context->connection;
        } else {
            $type = $this->_has_slave ? 'slave' : 'default';
            $connection = $this->poolManager->pop($this, $this->_timeout, $type);
        }

        try {
            return $connection->getTables($schema);
        } finally {
            if ($type) {
                $this->poolManager->push($this, $connection, $type);
            }
        }
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function buildSql($params)
    {
        $context = $this->_context;

        if ($context->connection) {
            $type = null;
            $connection = $context->connection;
        } else {
            $type = $this->_has_slave ? 'slave' : 'default';
            $connection = $this->poolManager->pop($this, $this->_timeout, $type);
        }

        try {
            return $connection->buildSql($params);
        } finally {
            if ($type) {
                $this->poolManager->push($this, $connection, $type);
            }
        }
    }

    /**
     * @param string $table
     *
     * @return array
     * @throws \ManaPHP\Db\Exception
     */
    public function getMetadata($table)
    {
        $context = $this->_context;

        if ($context->connection) {
            $type = null;
            $connection = $context->connection;
        } else {
            $type = $this->_has_slave ? 'slave' : 'default';
            $connection = $this->poolManager->pop($this, $this->_timeout, $type);
        }

        $table = $this->_completeTable($table);
        try {
            $start_time = microtime(true);
            $meta = $connection->getMetadata($table);
            $elapsed = round(microtime(true) - $start_time, 3);
        } finally {
            if ($type) {
                $this->poolManager->push($this, $connection, $type);
            }
        }

        $this->fireEvent('db:metadata', compact('elapsed', 'table', 'meta'));

        return $meta;
    }

    public function close()
    {
        $context = $this->_context;

        if ($context->connection) {
            if ($context->transaction_level !== 0) {
                $context->transaction_level = 0;
                try {
                    $context->connection->rollback();
                } finally {
                    $this->poolManager->push($this, $context->connection);
                }
                $this->fireEvent('db:abnormal');
            }
            $context->connection = null;
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