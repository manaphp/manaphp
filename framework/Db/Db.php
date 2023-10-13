<?php
declare(strict_types=1);

namespace ManaPHP\Db;

use JetBrains\PhpStorm\ArrayShape;
use ManaPHP\Context\ContextTrait;
use ManaPHP\Db\Event\DbBegin;
use ManaPHP\Db\Event\DbCommit;
use ManaPHP\Db\Event\DbDeleted;
use ManaPHP\Db\Event\DbDeleting;
use ManaPHP\Db\Event\DbExecuted;
use ManaPHP\Db\Event\DbExecuting;
use ManaPHP\Db\Event\DbInserted;
use ManaPHP\Db\Event\DbInserting;
use ManaPHP\Db\Event\DbMetadata;
use ManaPHP\Db\Event\DbQueried;
use ManaPHP\Db\Event\DbQuerying;
use ManaPHP\Db\Event\DbRollback;
use ManaPHP\Db\Event\DbUpdated;
use ManaPHP\Db\Event\DbUpdating;
use ManaPHP\Db\Exception as DbException;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NonCloneableException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Pooling\PoolManagerInterface;
use ManaPHP\Pooling\Transient;
use PDO;
use PDOException;
use Psr\EventDispatcher\EventDispatcherInterface;

class Db implements DbInterface
{
    use ContextTrait;

    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected PoolManagerInterface $poolManager;
    #[Autowired] protected MakerInterface $maker;

    public const METADATA_ATTRIBUTES = 0;
    public const METADATA_PRIMARY_KEY = 1;
    public const METADATA_AUTO_INCREMENT_KEY = 3;
    public const METADATA_INT_TYPE_ATTRIBUTES = 5;

    #[Autowired] protected string $uri;

    protected string $prefix = '';
    protected bool $has_slave = false;
    protected float $timeout = 3.0;
    protected string $pool_size = '4';

    /** @noinspection PhpTypedPropertyMightBeUninitializedInspection */
    public function __construct()
    {
        if (str_contains($this->uri, 'timeout=') && preg_match('#timeout=([\d.]+)#', $this->uri, $matches) === 1) {
            $this->timeout = (float)$matches[1];
        }

        if (preg_match('#pool_size=([\d/]+)#', $this->uri, $matches)) {
            $this->pool_size = $matches[1];
        }

        if (preg_match('#[?&]prefix=(\w+)#', $this->uri, $matches)) {
            $this->prefix = $matches[1];
        }

        $uris = [];
        if (str_contains($this->uri, '{') && preg_match('#{[^}]+}#', $this->uri, $matches)) {
            $hosts = $matches[0];
            foreach (explode(',', substr($hosts, 1, -1)) as $value) {
                $value = trim($value);
                $uris[] = $value === '' ? $value : str_replace($hosts, $value, $this->uri);
            }
        } elseif (str_contains($this->uri, ',')) {
            $hosts = parse_url($this->uri, PHP_URL_HOST);
            if (str_contains($hosts, ',')) {
                foreach (explode(',', $hosts) as $value) {
                    $value = trim($value);
                    $uris[] = $value === '' ? $value : str_replace($hosts, $value, $this->uri);
                }
            } else {
                foreach (explode(',', $this->uri) as $value) {
                    $uris[] = trim($value);
                }
            }
        } else {
            $uris[] = $this->uri;
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
            $this->poolManager->add($this, [ConnectionInterface::class, ['uri' => $uri]], $master_pool_size);
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
                        $this->poolManager->add($this, [ConnectionInterface::class, ['uri' => $v]], 1, 'slave');
                    }
                }
            } else {
                $uri = (string)$uris[random_int(0, count($uris) - 1)];
                $this->poolManager->add($this, [ConnectionInterface::class, ['uri' => $uri]], 1, 'slave');
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
                     'delete' => [DbDeleting::class, DbDeleted::class],
                     'update' => [DbUpdating::class, DbUpdated::class],
                     'insert' => [DbInserting::class, DbInserted::class]
                 ][$type] ?? null;

        /** @var DbContext $context */
        $context = $this->getContext();

        $this->eventDispatcher->dispatch(new DbExecuting($this, $type, $sql, $bind));
        $event && $this->eventDispatcher->dispatch(new $event[0]($this, $type, $sql, $bind));

        if ($context->connection) {
            $connection = $context->connection;
        } else {
            $connection = $this->poolManager->pop($this, $this->timeout);
        }

        try {
            $start_time = microtime(true);
            $count = $connection->execute($sql, $bind);
            $elapsed = round(microtime(true) - $start_time, 3);
        } finally {
            if (!$context->connection) {
                $this->poolManager->push($this, $connection);
            }
        }

        $event && $this->eventDispatcher->dispatch(new $event[1]($this, $type, $sql, $bind, $count, $elapsed));
        $this->eventDispatcher->dispatch(new DbExecuted($this, $type, $sql, $bind, $count, $elapsed));

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

    public function fetchOne(string $sql, array $bind = [], int $mode = PDO::FETCH_ASSOC, bool $useMaster = false
    ): ?array {
        return $this->fetchAll($sql, $bind, $mode, $useMaster)[0] ?? null;
    }

    public function fetchAll(string $sql, array $bind = [], int $mode = PDO::FETCH_ASSOC, bool $useMaster = false
    ): array {
        /** @var DbContext $context */
        $context = $this->getContext();

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

        $this->eventDispatcher->dispatch(new DbQuerying($this, $sql, $bind));

        try {
            $start_time = microtime(true);
            $result = $connection->query($sql, $bind, $mode);
            $elapsed = round(microtime(true) - $start_time, 3);
        } finally {
            if ($type) {
                $this->poolManager->push($this, $connection, $type);
            }
        }

        $count = count($result);
        $this->eventDispatcher->dispatch(new DbQueried($this, $sql, $bind, $count, $result, $elapsed));

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
        /** @var DbContext $context */
        $context = $this->getContext();

        $table = $this->completeTable($table);

        if (!$record) {
            throw new InvalidArgumentException(['Unable to insert into {table} table without data', 'table' => $table]);
        }
        $fields = array_keys($record);
        $insertedValues = ':' . implode(',:', $fields);
        $insertedFields = '[' . implode('],[', $fields) . ']';

        $sql
            = /** @lang text */
            "INSERT INTO $table ($insertedFields) VALUES ($insertedValues)";
        $bind = $record;

        $affected_rows = 0;

        $connection = $context->connection ?: $this->poolManager->pop($this, $this->timeout);

        $this->eventDispatcher->dispatch(new DbInserting($this, 'insert', $sql, $bind));

        try {
            $start_time = microtime(true);
            if ($fetchInsertId) {
                $insert_id = $connection->execute($sql, $record, true);
                $affected_rows = 1;
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

        $this->eventDispatcher->dispatch(
            new DbInserted($this, 'insert', $sql, $bind, $affected_rows, $elapsed)
        );

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
            throw new InvalidArgumentException(['Unable to update {table} table without data', 'table' => $table]);
        }

        if (!$conditions) {
            throw new NotSupportedException(['update must with a condition!']);
        }

        $wheres = [];

        foreach ((array)$conditions as $k => $v) {
            if (is_int($k)) {
                if (!is_string($v) || $v === '' || preg_match('#^\w+$#', $v) === 1) {
                    throw new NotSupportedException(['update with `{1}` condition is danger!', json_stringify($v)]);
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

        if ($this->query($table)->where([$primaryKey => $insertFieldValues[$primaryKey]])->exists()) {
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
                    throw new NotSupportedException(['delete with `{1}` condition is danger!', json_stringify($v)]);
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

    public function begin(): void
    {
        /** @var DbContext $context */
        $context = $this->getContext();

        if ($context->transaction_level === 0) {
            $this->eventDispatcher->dispatch(new DbBegin($this));

            /** @var ConnectionInterface $connection */
            $connection = $this->poolManager->pop($this, $this->timeout);

            try {
                $connection->begin();
                $context->connection = $connection;
                $context->transaction_level++;
            } catch (PDOException $exception) {
                $message = 'beginTransaction failed: ' . $exception->getMessage();
                throw new DbException($message, $exception->getCode(), $exception);
            } finally {
                /** @noinspection PhpConditionAlreadyCheckedInspection */
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
        /** @var DbContext $context */
        $context = $this->getContext();

        return $context->transaction_level !== 0;
    }

    public function rollback(): void
    {
        /** @var DbContext $context */
        $context = $this->getContext();

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
                    $this->eventDispatcher->dispatch(new DbRollback($this));
                }
            }
        }
    }

    public function commit(): void
    {
        /** @var DbContext $context */
        $context = $this->getContext();

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
                $this->eventDispatcher->dispatch(new DbCommit($this));
            }
        }
    }

    public function getTables(?string $schema = null): array
    {
        /** @var DbContext $context */
        $context = $this->getContext();

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
        /** @var DbContext $context */
        $context = $this->getContext();

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

    #[ArrayShape([self::METADATA_ATTRIBUTES          => 'array',
                  self::METADATA_PRIMARY_KEY         => 'array',
                  self::METADATA_AUTO_INCREMENT_KEY  => '\mixed|null',
                  self::METADATA_INT_TYPE_ATTRIBUTES => 'array'])]
    public function getMetadata(string $table): array
    {
        /** @var DbContext $context */
        $context = $this->getContext();

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

        $this->eventDispatcher->dispatch(new DbMetadata($this, $table, $meta, $elapsed));

        return $meta;
    }

    public function close(): void
    {
        /** @var DbContext $context */
        $context = $this->getContext();

        if ($context->connection) {
            if ($context->transaction_level !== 0) {
                $context->transaction_level = 0;
                try {
                    $context->connection->rollback();
                } finally {
                    $this->poolManager->push($this, $context->connection);
                }
            }
            $context->connection = null;
        }
    }

    public function query(?string $table = null, ?string $alias = null): Query
    {
        return $this->maker->make(Query::class, [$this])->from($table, $alias);
    }

    public function getTransientWrapper(string $type = 'default'): Transient
    {
        return $this->poolManager->transient($this, $this->timeout, $type);
    }

    public function transientCall(object $instance, string $method, array $arguments): mixed
    {
        /** @var DbContext $context */
        $context = $this->getContext();

        if ($context->connection !== null) {
            throw new MisuseException('');
        }

        if (!$instance instanceof ConnectionInterface) {
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