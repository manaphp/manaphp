<?php
declare(strict_types=1);

namespace ManaPHP\Data;

use JetBrains\PhpStorm\ArrayShape;
use ManaPHP\Component;
use ManaPHP\Data\Db\Exception as DbException;
use ManaPHP\Data\Db\Query;
use ManaPHP\Data\Db\SqlFragmentable;
use ManaPHP\Di\FactoryInterface;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NonCloneableException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Pool\Transient;
use PDO;
use PDOException;

/**
 * @property-read \ManaPHP\Pool\ManagerInterface $poolManager
 * @property-read \ManaPHP\Data\DbContext        $context
 */
class Db extends Component implements DbInterface
{
    public const METADATA_ATTRIBUTES = 0;
    public const METADATA_PRIMARY_KEY = 1;
    public const METADATA_AUTO_INCREMENT_KEY = 3;
    public const METADATA_INT_TYPE_ATTRIBUTES = 5;

    protected string $uri;
    protected FactoryInterface $factory;
    protected string $prefix = '';
    protected bool $has_slave = false;
    protected float $timeout = 3.0;
    protected string $pool_size = '4';

    public function __construct(string $uri, FactoryInterface $factory)
    {
        $this->uri = $uri;
        $this->factory = $factory;

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
            $sample = $factory->make($adapter, [$uri]);
            $this->poolManager->add($this, $sample, $master_pool_size);
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
                        $sample = $factory->make($adapter, [$v]);
                        $this->poolManager->add($this, $sample, 1, 'slave');
                    }
                }
            } else {
                $uri = (string)$uris[random_int(0, count($uris) - 1)];
                $adapter = 'ManaPHP\Data\Db\Connection\Adapter\\' . ucfirst(parse_url($uri, PHP_URL_SCHEME));
                $sample = $factory->make($adapter, [$uri]);
                $this->poolManager->add($this, $sample, 1, 'slave');
            }

            $this->has_slave = true;
        }
    }

    public function __clone()
    {
        throw new NonCloneableException($this);
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function execute(string $type, string $sql, array $bind = []): int
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

    public function executeInsert(string $sql, array $bind = []): int
    {
        return $this->execute('insert', $sql, $bind);
    }

    public function executeUpdate(string $sql, array $bind = []): int
    {
        return $this->execute('update', $sql, $bind);
    }

    public function executeDelete(string $sql, array $bind = []): int
    {
        return $this->execute('delete', $sql, $bind);
    }

    public function affectedRows(): int
    {
        return $this->context->affected_rows;
    }

    public function fetchOne(string $sql, array $bind = [], int $mode = PDO::FETCH_ASSOC, bool $useMaster = false
    ): false|array {
        return ($rs = $this->fetchAll($sql, $bind, $mode, $useMaster)) ? $rs[0] : false;
    }

    public function fetchAll(string $sql, array $bind = [], int $mode = PDO::FETCH_ASSOC, bool $useMaster = false
    ): array {
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

    protected function completeTable(string $table): string
    {
        if (($pos = strpos($table, '.')) === false) {
            return '[' . $this->prefix . $table . ']';
        } else {
            return '[' . substr($table, 0, $pos) . '].[' . $this->prefix . substr($table, $pos + 1) . ']';
        }
    }

    public function insert(string $table, array $record, bool $fetchInsertId = false): mixed
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

    public function insertBySql(string $table, string $sql, array $bind = []): int
    {
        $table = $this->completeTable($table);

        return $this->execute('insert', /**@lang text */ "INSERT INTO $table $sql", $bind);
    }

    public function update(string $table, array $fieldValues, string|array $conditions, array $bind = []): int
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

    public function updateBySql(string $table, string $sql, array $bind = []): int
    {
        $table = $this->completeTable($table);

        return $this->execute('update', /** @lang text */ "UPDATE $table SET $sql", $bind);
    }

    public function upsert(string $table, array $insertFieldValues, array $updateFieldValues = [],
        ?string $primaryKey = null
    ): int {
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

    public function delete(string $table, string|array $conditions, array $bind = []): int
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

    public function deleteBySql(string $table, string $sql, array $bind = []): int
    {
        $table = $this->completeTable($table);

        return $this->execute('delete', /**@lang text */ "DELETE FROM $table WHERE $sql", $bind);
    }

    public function getSQL(): string
    {
        return $this->context->sql;
    }

    public function getBind(): array
    {
        return $this->context->bind;
    }

    public function begin(): void
    {
        $context = $this->context;

        if ($context->transaction_level === 0) {
            $this->fireEvent('db:begin');

            /** @var \ManaPHP\Data\Db\ConnectionInterface $connection */
            $connection = $this->poolManager->pop($this, $this->timeout);

            try {
                $connection->begin();
                $context->connection = $connection;
                $context->transaction_level++;
            } catch (PDOException $exception) {
                $message = 'beginTransaction failed: ' . $exception->getMessage();
                throw new DbException($message, $exception->getCode(), $exception);
            } finally {
                if ($context->connection !== null) {
                    $this->poolManager->push($this, $connection);
                }
            }
        } else {
            $context->transaction_level++;
        }
    }

    public function isUnderTransaction(): bool
    {
        $context = $this->context;

        return $context->transaction_level !== 0;
    }

    public function rollback(): void
    {
        $context = $this->context;

        if ($context->transaction_level > 0) {
            $context->transaction_level--;

            if ($context->transaction_level === 0) {
                try {
                    $context->connection->rollback();
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

    public function commit(): void
    {
        $context = $this->context;

        if ($context->transaction_level === 0) {
            throw new MisuseException('There is no active transaction');
        }

        $context->transaction_level--;

        if ($context->transaction_level === 0) {
            try {
                $context->connection->commit();
            } catch (PDOException $exception) {
                throw new DbException('commit failed: ' . $exception->getMessage(), $exception->getCode(), $exception);
            } finally {
                $this->poolManager->push($this, $context->connection);
                $context->connection = null;
                $this->fireEvent('db:commit');
            }
        }
    }

    public function getLastSql(): string
    {
        return $this->context->sql;
    }

    public function getTables(?string $schema = null): array
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

    public function buildSql(array $params): string
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

    #[ArrayShape([self::METADATA_ATTRIBUTES          => "array",
                  self::METADATA_PRIMARY_KEY         => "array",
                  self::METADATA_AUTO_INCREMENT_KEY  => "\mixed|null",
                  self::METADATA_INT_TYPE_ATTRIBUTES => "array"])]
    public function getMetadata(string $table): array
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

    public function close(): void
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

    public function query(?string $table = null, ?string $alias = null): Query
    {
        return $this->factory->make('ManaPHP\Data\Db\Query', [$this])->from($table, $alias);
    }

    public function getTransientWrapper(string $type = 'default'): Transient
    {
        return $this->poolManager->transient($this, $this->timeout, $type);
    }

    public function transientCall(object $instance, string $method, array $arguments): mixed
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