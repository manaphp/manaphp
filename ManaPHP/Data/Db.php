<?php

namespace ManaPHP\Data;

use ManaPHP\Component;
use ManaPHP\Data\Db\Exception as DbException;
use ManaPHP\Data\Db\SqlFragmentable;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NonCloneableException;
use ManaPHP\Exception\NotSupportedException;
use PDO;
use PDOException;

/**
 * @property-read \ManaPHP\Pool\ManagerInterface $poolManager
 * @property-read \ManaPHP\Data\DbContext        $context
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
    protected $uri;

    /**
     * @var string
     */
    protected $prefix = '';

    /**
     * @var bool
     */
    protected $has_slave = false;

    /**
     * @var float
     */
    protected $timeout = 3.0;

    /**
     * @var string
     */
    protected $pool_size = '4';

    /**
     * @param string $uri
     */
    public function __construct($uri)
    {
        $this->uri = $uri;

        if (str_contains($uri, 'timeout=') && preg_match('#timeout=([\d.]+)#', $uri, $matches) === 1) {
            $this->timeout = (float)$matches[1];
        }

        if (preg_match('#pool_size=([\d/]+)#', $uri, $matches)) {
            $this->pool_size = $matches[1];
        }

        if (preg_match('#[?&]prefix=(\w+)#', $uri, $matches)) {
            $this->prefix = $matches[1];
        }

        $uris = [];
        if (str_contains($uri, '{') && preg_match('#{[^}]+}#', $uri, $matches)) {
            $hosts = $matches[0];
            foreach (explode(',', substr($hosts, 1, -1)) as $value) {
                $value = trim($value);
                $uris[] = $value === '' ? $value : str_replace($hosts, $value, $uri);
            }
        } elseif (str_contains($uri, ',')) {
            $hosts = parse_url($uri, PHP_URL_HOST);
            if (str_contains($hosts, ',')) {
                foreach (explode(',', $hosts) as $value) {
                    $value = trim($value);
                    $uris[] = $value === '' ? $value : str_replace($hosts, $value, $uri);
                }
            } else {
                foreach (explode(',', $uri) as $value) {
                    $uris[] = trim($value);
                }
            }
        } else {
            $uris[] = $uri;
        }

        if (($pos = strpos($this->pool_size, '/')) === false) {
            $master_pool_size = (int)$this->pool_size;
            $slave_pool_size = (int)$this->pool_size;
        } else {
            $master_pool_size = (int)substr($this->pool_size, 0, $pos);
            $slave_pool_size = (int)substr($this->pool_size, $pos + 1);
        }

        if ($uris[0] !== '') {
            $uri = (string)$uris[0];
            $adapter = 'ManaPHP\Data\Db\Connection\Adapter\\' . ucfirst(parse_url($uri, PHP_URL_SCHEME));
            $this->poolManager->add($this, ['class' => $adapter, $uri], $master_pool_size);
        }

        if (count($uris) > 1) {
            array_shift($uris);

            foreach ($uris as $i => $v) {
                if (preg_match('#[?&]readonly\b#', $v) !== 1) {
                    $uris[$i] .= (str_contains($v, '?') ? '&' : '?') . 'readonly=1';
                }
            }

            if (MANAPHP_COROUTINE_ENABLED) {
                shuffle($uris);

                $this->poolManager->create($this, count($uris) * $slave_pool_size, 'slave');
                for ($i = 0; $i < $slave_pool_size; $i++) {
                    foreach ($uris as $v) {
                        $adapter = 'ManaPHP\Data\Db\Connection\Adapter\\' . ucfirst(parse_url($v, PHP_URL_SCHEME));
                        $this->poolManager->add($this, ['class' => $adapter, $v], 1, 'slave');
                    }
                }
            } else {
                $uri = (string)$uris[random_int(0, count($uris) - 1)];
                $adapter = 'ManaPHP\Data\Db\Connection\Adapter\\' . ucfirst(parse_url($uri, PHP_URL_SCHEME));
                $this->poolManager->add($this, ['class' => $adapter, $uri], 1, 'slave');
            }

            $this->has_slave = true;
        }
    }

    public function __destruct()
    {
        $this->poolManager->remove($this);
    }

    public function __clone()
    {
        throw new NonCloneableException($this);
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param string $type
     * @param string $sql
     * @param array  $bind
     *
     * @return int
     * @throws \ManaPHP\Data\Db\Exception
     */
    public function execute($type, $sql, $bind = [])
    {
        $event = [
                     'delete' => ['deleting', 'deleted'],
                     'update' => ['updating', 'updated'],
                     'insert' => ['inserting', 'inserted']
                 ][$type] ?? null;

        $context = $this->context;

        $context->sql = $sql;
        $context->bind = $bind;

        $context->affected_rows = 0;

        $this->fireEvent('db:executing', compact('type', 'sql', 'bind'));
        $event && $this->fireEvent('db:' . $event[0], compact('type', 'sql', 'bind'));

        if ($context->connection) {
            $connection = $context->connection;
        } else {
            $connection = $this->poolManager->pop($this, $this->timeout);
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

        $event && $this->fireEvent('db:' . $event[1], compact('type', 'count', 'sql', 'bind', 'elapsed'));
        $this->fireEvent('db:executed', compact('type', 'count', 'sql', 'bind', 'elapsed'));

        return $count;
    }

    /**
     * @param string $sql
     * @param array  $bind
     *
     * @return int
     */
    public function executeInsert($sql, $bind = [])
    {
        return $this->execute('insert', $sql, $bind);
    }

    /**
     * @param string $sql
     * @param array  $bind
     *
     * @return int
     */
    public function executeUpdate($sql, $bind = [])
    {
        return $this->execute('update', $sql, $bind);
    }

    /**
     * @param string $sql
     * @param array  $bind
     *
     * @return int
     */
    public function executeDelete($sql, $bind = [])
    {
        return $this->execute('delete', $sql, $bind);
    }

    /**
     * Returns the number of affected rows by the last INSERT/UPDATE/DELETE reported by the database system
     *
     * @return int
     */
    public function affectedRows()
    {
        return $this->context->affected_rows;
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
        $context = $this->context;

        $context->sql = $sql;
        $context->bind = $bind;
        $context->affected_rows = 0;

        $this->fireEvent('db:querying');

        if ($context->connection) {
            $type = null;
            $connection = $context->connection;
        } else {
            if ($useMaster) {
                $type = 'default';
            } else {
                $type = $this->has_slave ? 'slave' : 'default';
            }

            $connection = $this->poolManager->pop($this, $this->timeout, $type);
        }

        $sql = $connection->replaceQuoteCharacters($sql);
        try {
            $start_time = microtime(true);
            $result = $connection->query($sql, $bind, $mode);
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
    protected function completeTable($table)
    {
        if (($pos = strpos($table, '.')) === false) {
            return '[' . $this->prefix . $table . ']';
        } else {
            return '[' . substr($table, 0, $pos) . '].[' . $this->prefix . substr($table, $pos + 1) . ']';
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
        $context = $this->context;

        $table = $this->completeTable($table);

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

        $connection = $context->connection ?: $this->poolManager->pop($this, $this->timeout);

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

        $this->fireEvent('db:inserted', compact('sql', 'record', 'elapsed', 'insert_id', 'bind'));

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
        $table = $this->completeTable($table);

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
     * @throws \ManaPHP\Data\Db\Exception
     */
    public function update($table, $fieldValues, $conditions, $bind = [])
    {
        $table = $this->completeTable($table);

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
                    throw new NotSupportedException(['update with `%s` condition is danger!', json_stringify($v)]);
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
        $table = $this->completeTable($table);

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
     * @throws \ManaPHP\Data\Db\Exception
     */
    public function delete($table, $conditions, $bind = [])
    {
        $table = $this->completeTable($table);

        if (!$conditions) {
            throw new NotSupportedException(['delete must with a condition!']);
        }

        $wheres = [];
        foreach ((array)$conditions as $k => $v) {
            if (is_int($k)) {
                if (!is_string($v) || $v === '' || ($v !== 'FALSE' && preg_match('#^\w+$#', $v) === 1)) {
                    throw new NotSupportedException(['delete with `%s` condition is danger!', json_stringify($v)]);
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
        $table = $this->completeTable($table);

        return $this->execute('delete', /**@lang text */ "DELETE FROM $table WHERE $sql", $bind);
    }

    /**
     * Active SQL statement in the object
     *
     * @return string
     */
    public function getSQL()
    {
        return $this->context->sql;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function quote($value)
    {
        return "'" . str_replace($value, "'", "\\'") . "'";
    }

    /**
     * @param mixed $value
     * @param int   $preservedStrLength
     *
     * @return int|string
     */
    protected function parseBindValue($value, $preservedStrLength)
    {
        if (is_string($value)) {
            $quoted = $this->quote($value);
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
        $context = $this->context;

        if (!$context->bind) {
            return (string)$context->sql;
        }

        $bind = $context->bind;
        if (isset($bind[0])) {
            return (string)$context->sql;
        } else {
            $replaces = [];
            foreach ($bind as $key => $value) {
                $replaces[':' . $key] = $this->parseBindValue($value, $preservedStrLength);
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
        return $this->context->bind;
    }

    /**
     * Starts a transaction in the connection
     *
     * @return void
     * @throws \ManaPHP\Data\Db\Exception
     */
    public function begin()
    {
        $context = $this->context;

        if ($context->transaction_level === 0) {
            $this->fireEvent('db:begin');

            /** @var \ManaPHP\Data\Db\ConnectionInterface $connection */
            $connection = $this->poolManager->pop($this, $this->timeout);

            try {
                if (!$connection->begin()) {
                    throw new DbException('beginTransaction failed.');
                }
                $context->connection = $connection;
                $context->transaction_level++;
            } catch (PDOException $exception) {
                $message = 'beginTransaction failed: ' . $exception->getMessage();
                throw new DbException($message, $exception->getCode(), $exception);
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
        $context = $this->context;

        return $context->transaction_level !== 0;
    }

    /**
     * Rollbacks the active transaction in the connection
     *
     * @return void
     * @throws \ManaPHP\Data\Db\Exception
     */
    public function rollback()
    {
        $context = $this->context;

        if ($context->transaction_level > 0) {
            $context->transaction_level--;

            if ($context->transaction_level === 0) {
                try {
                    if (!$context->connection->rollback()) {
                        throw new DbException('rollBack failed.');
                    }
                } catch (PDOException $exception) {
                    $message = 'rollBack failed: ' . $exception->getMessage();
                    throw new DbException($message, $exception->getCode(), $exception);
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
     * @throws \ManaPHP\Data\Db\Exception
     */
    public function commit()
    {
        $context = $this->context;

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
        return $this->context->sql;
    }

    /**
     * @param string $schema
     *
     * @return array
     * @throws \ManaPHP\Data\Db\Exception
     */
    public function getTables($schema = null)
    {
        $context = $this->context;

        if ($context->connection) {
            $type = null;
            $connection = $context->connection;
        } else {
            $type = $this->has_slave ? 'slave' : 'default';
            $connection = $this->poolManager->pop($this, $this->timeout, $type);
        }

        try {
            if ($this->prefix === '') {
                return $connection->getTables($schema);
            } else {
                $prefix = $this->prefix;
                $prefix_len = strlen($prefix);
                $tables = [];
                foreach ($connection->getTables($schema) as $table) {
                    if (str_starts_with($table, $prefix)) {
                        $tables[] = substr($table, $prefix_len);
                    }
                }
                return $tables;
            }
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
        $context = $this->context;

        if ($context->connection) {
            $type = null;
            $connection = $context->connection;
        } else {
            $type = $this->has_slave ? 'slave' : 'default';
            $connection = $this->poolManager->pop($this, $this->timeout, $type);
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
     * @throws \ManaPHP\Data\Db\Exception
     */
    public function getMetadata($table)
    {
        $context = $this->context;

        if ($context->connection) {
            $type = null;
            $connection = $context->connection;
        } else {
            $type = $this->has_slave ? 'slave' : 'default';
            $connection = $this->poolManager->pop($this, $this->timeout, $type);
        }

        $table = $this->completeTable($table);
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

    /**
     * @return void
     */
    public function close()
    {
        $context = $this->context;

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
     * @return \ManaPHP\Data\Db\Query
     */
    public function query($table = null, $alias = null)
    {
        return $this->container->make('ManaPHP\Data\Db\Query', [$this])->from($table, $alias);
    }

    public function getTransientWrapper($type = 'default')
    {
        return $this->poolManager->transient($this, $this->timeout, $type);
    }

    public function transientCall($instance, $method, $arguments)
    {
        $context = $this->context;

        if ($context->connection !== null) {
            throw new MisuseException('');
        }

        $context->connection = $instance;
        try {
            return $this->{$method}(...$arguments);
        } finally {
            $context->connection = null;
        }
    }
}