<?php
declare(strict_types=1);

namespace ManaPHP\Data\Db;

use JsonSerializable;
use ManaPHP\Component;
use ManaPHP\Data\Db\Exception as DbException;
use ManaPHP\Exception\NotSupportedException;
use PDO;
use PDOException;
use PDOStatement;

abstract class AbstractConnection extends Component implements ConnectionInterface
{
    protected string $uri;
    protected string $dsn;
    protected string $username;
    protected string $password;
    protected bool $emulate_prepares = false;
    protected array $options = [];
    protected ?PDO $pdo = null;
    protected bool $in_transaction = false;
    protected array $prepared = [];
    protected bool $readonly = false;
    protected ?float $last_heartbeat = null;

    public function __construct()
    {
        $this->options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        $this->options[PDO::ATTR_EMULATE_PREPARES] = $this->emulate_prepares;
    }

    public function __clone()
    {
        $this->pdo = null;
        $this->in_transaction = false;
        $this->last_heartbeat = null;
        $this->prepared = [];
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    /** @noinspection PhpUnusedLocalVariableInspection */
    protected function ping(): bool
    {
        try {
            @$this->pdo->query("SELECT 'PING'")->fetchAll();
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    protected function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $dsn = $this->dsn;
            $uri = $this->uri;

            $this->fireEvent('db:connecting', compact('dsn', 'uri'));

            try {
                $params = [$dsn, $this->username, $this->password, $this->options];
                $this->pdo = $pdo = $this->container->make('PDO', $params);
            } catch (PDOException $e) {
                $this->fireEvent('db:connected', compact('dsn', 'uri'));

                $code = $e->getCode();
                throw new ConnectionException(['connect `%s` failed: %s', $dsn, $e->getMessage()], $code, $e);
            }

            $this->fireEvent('db:connected', compact('dsn', 'uri', 'pdo'));
        }

        return $this->pdo;
    }

    protected function escapeIdentifier(string $identifier): string
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

    protected function getPrepared(string $sql): PDOStatement
    {
        if (!isset($this->prepared[$sql])) {
            if (count($this->prepared) > 8) {
                array_shift($this->prepared);
            }
            return $this->prepared[$sql] = @$this->getPdo()->prepare($sql);
        }
        return $this->prepared[$sql];
    }

    protected function executeInternal(string $sql, array $bind): PDOStatement
    {
        $statement = $this->getPrepared($sql);

        $tr = [];
        foreach ($bind as $parameter => $value) {
            if (is_scalar($value) || $value === null) {
                null;
            } elseif (is_array($value)) {
                $value = json_stringify($value);
            } elseif ($value instanceof JsonSerializable) {
                $value = json_stringify($value);
            } else {
                $type = gettype($value);
                throw new NotSupportedException(['The `:1` type of `:2` parameter is not support', $parameter, $type]);
            }

            if (is_int($parameter)) {
                $tr[$parameter + 1] = $value;
            } else {
                $tr[$parameter[0] === ':' ? $parameter : ':' . $parameter] = $value;
            }
        }

        @$statement->execute($tr);

        $this->last_heartbeat = microtime(true);

        return $statement;
    }

    /** @noinspection PhpUnusedLocalVariableInspection */
    public function execute(string $sql, array $bind = [], bool $has_insert_id = false): int
    {
        if ($this->readonly) {
            throw new ReadonlyException(['`:uri` is readonly: => :sql ', 'uri' => $this->uri, 'sql' => $sql]);
        }

        $sql = $this->replaceQuoteCharacters($sql);

        if ($this->in_transaction) {
            try {
                $r = $bind ? $this->executeInternal($sql, $bind)->rowCount() : @$this->getPdo()->exec($sql);
                return $has_insert_id ? (int)$this->getPdo()->lastInsertId() : $r;
            } catch (PDOException $exception) {
            }
        } else {
            try {
                $r = $bind ? $this->executeInternal($sql, $bind)->rowCount() : @$this->getPdo()->exec($sql);
                return $has_insert_id ? (int)$this->getPdo()->lastInsertId() : $r;
            } catch (PDOException $exception) {
                try {
                    $this->close();
                    $r = $bind
                        ? $this->executeInternal($sql, $bind)->rowCount()
                        : @$this->getPdo()->exec(
                            $sql
                        );
                    return $has_insert_id ? (int)$this->getPdo()->lastInsertId() : $r;
                } catch (PDOException $exception) {
                }
            }
        }

        $bind_str = json_stringify($bind, JSON_PRETTY_PRINT);
        throw new DbException(["%s =>\r\n SQL: %s\r\n BIND: %s", $exception->getMessage(), $sql, $bind_str]);
    }

    /** @noinspection PhpUnusedLocalVariableInspection */
    public function query(string $sql, array $bind = [], int $mode = PDO::FETCH_ASSOC): array
    {
        $sql = $this->replaceQuoteCharacters($sql);

        if ($this->in_transaction) {
            try {
                $statement = $bind ? $this->executeInternal($sql, $bind) : @$this->getPdo()->query($sql);
                return $statement->fetchAll($mode);
            } catch (PDOException $exception) {
            }
        } else {
            try {
                $statement = $bind ? $this->executeInternal($sql, $bind) : @$this->getPdo()->query($sql);
                return $statement->fetchAll($mode);
            } catch (PDOException $exception) {
                try {
                    $this->close();
                    $statement = $bind
                        ? $this->executeInternal($sql, $bind)
                        : @$this->getPdo()->query(
                            $sql
                        );
                    return $statement->fetchAll($mode);
                } catch (PDOException $exception) {
                }
            }
        }

        $bind_str = json_stringify($bind, JSON_PRETTY_PRINT);
        throw new DbException(["%s =>\r\n SQL: %s\r\n BIND: %s", $exception->getMessage(), $sql, $bind_str]);
    }

    public function close(): void
    {
        if ($this->pdo) {
            $dsn = $this->dsn;
            $uri = $this->uri;
            $pdo = $this->pdo;
            $this->fireEvent('db:close', compact('dsn', 'uri', 'pdo'));

            if ($this->in_transaction) {
                $this->in_transaction = false;
                $this->fireEvent('db:abnormal', compact('dsn', 'uri', 'pdo'));
            }

            $this->pdo = null;
            $this->last_heartbeat = null;
            $this->prepared = [];
        }
    }

    public function getLastHeartbeat(): ?float
    {
        return $this->last_heartbeat;
    }

    /** @noinspection PhpUnusedLocalVariableInspection */
    public function begin(): void
    {
        if ($this->readonly) {
            throw new ReadonlyException(['`:uri` is readonly, transaction begin failed', 'uri' => $this->uri]);
        }

        try {
            $this->in_transaction = $this->getPdo()->beginTransaction();
        } catch (PDOException $exception) {
            try {
                $this->close();
                $this->in_transaction = $this->getPdo()->beginTransaction();
            } catch (PDOException $exception) {
                throw new DbException($exception);
            }
        }
    }

    public function rollback(): void
    {
        $this->in_transaction = false;
        @$this->pdo->rollBack();
    }

    public function commit(): void
    {
        $this->in_transaction = false;
        @$this->pdo->commit();
    }
}