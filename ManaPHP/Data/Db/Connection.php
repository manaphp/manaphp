<?php

namespace ManaPHP\Data\Db;

use JsonSerializable;
use ManaPHP\Component;
use ManaPHP\Data\Db\Exception as DbException;
use ManaPHP\Exception\NotSupportedException;
use PDO;
use PDOException;

abstract class Connection extends Component implements ConnectionInterface
{
    /**
     * @var string
     */
    protected $uri;

    /**
     * @var string
     */
    protected $dsn;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var bool
     */
    protected $emulate_prepares = false;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var \PDO
     */
    protected $pdo;

    /**
     * @var bool
     */
    protected $in_transaction = false;

    /**
     * @var \PDOStatement[]
     */
    protected $prepared = [];

    /**
     * @var bool
     */
    protected $readonly = false;

    /**
     * @var float
     */
    protected $last_heartbeat;

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

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @return bool
     */
    protected function ping()
    {
        try {
            @$this->pdo->query("SELECT 'PING'")->fetchAll();
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * @return \PDO
     */
    protected function getPdo()
    {
        if ($this->pdo === null) {
            $dsn = $this->dsn;
            $uri = $this->uri;

            $this->fireEvent('db:connecting', compact('dsn', 'uri'));

            try {
                $params = [$dsn, $this->username, $this->password, $this->options];
                $this->pdo = $pdo = $this->getNew('PDO', $params);
            } catch (PDOException $e) {
                $this->fireEvent('db:connected', compact('dsn', 'uri'));

                $code = $e->getCode();
                throw new ConnectionException(['connect `%s` failed: %s', $dsn, $e->getMessage()], $code, $e);
            }

            $this->fireEvent('db:connected', compact('dsn', 'uri', 'pdo'));
        }

        return $this->pdo;
    }

    /**
     * @param string $identifier
     *
     * @return string
     */
    protected function escapeIdentifier($identifier)
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
     * @param string $sql
     *
     * @return \PDOStatement
     */
    protected function getPrepared($sql)
    {
        if (!isset($this->prepared[$sql])) {
            if (count($this->prepared) > 8) {
                array_shift($this->prepared);
            }
            return $this->prepared[$sql] = @$this->self->getPdo()->prepare($sql);
        }
        return $this->prepared[$sql];
    }

    /**
     * @param string $sql
     * @param array  $bind
     *
     * @return \PDOStatement
     */
    protected function executeInternal($sql, $bind)
    {
        $statement = $this->self->getPrepared($sql);

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

    /**
     * @param string $sql
     * @param array  $bind
     * @param bool   $has_insert_id
     *
     * @return int
     * @throws \ManaPHP\Data\Db\ConnectionException
     * @throws \ManaPHP\Exception\NotSupportedException
     */
    public function execute($sql, $bind = [], $has_insert_id = false)
    {
        if ($this->readonly) {
            throw new ReadonlyException(['`:uri` is readonly: => :sql ', 'uri' => $this->uri, 'sql' => $sql]);
        }

        $sql = $this->self->replaceQuoteCharacters($sql);

        if ($this->in_transaction) {
            try {
                $r = $bind ? $this->self->executeInternal($sql, $bind)->rowCount() : @$this->self->getPdo()->exec($sql);
                return $has_insert_id ? $this->self->getPdo()->lastInsertId() : $r;
            } catch (PDOException $exception) {
            }
        } else {
            try {
                $r = $bind ? $this->self->executeInternal($sql, $bind)->rowCount() : @$this->self->getPdo()->exec($sql);
                return $has_insert_id ? $this->self->getPdo()->lastInsertId() : $r;
            } catch (PDOException $exception) {
                try {
                    $this->self->close();
                    $r = $bind
                        ? $this->self->executeInternal($sql, $bind)->rowCount()
                        : @$this->self->getPdo()->exec(
                            $sql
                        );
                    return $has_insert_id ? $this->self->getPdo()->lastInsertId() : $r;
                } catch (PDOException $exception) {
                }
            }
        }

        $bind_str = json_stringify($bind, JSON_PRETTY_PRINT);
        throw new DbException(["%s =>\r\n SQL: %s\r\n BIND: %s", $exception->getMessage(), $sql, $bind_str]);
    }

    /**
     * @param string $sql
     * @param array  $bind
     * @param int    $mode
     *
     * @return array
     */
    public function query($sql, $bind = [], $mode = PDO::FETCH_ASSOC)
    {
        $sql = $this->self->replaceQuoteCharacters($sql);

        if ($this->in_transaction) {
            try {
                $statement = $bind ? $this->self->executeInternal($sql, $bind) : @$this->self->getPdo()->query($sql);
                return $statement->fetchAll($mode);
            } catch (PDOException $exception) {
            }
        } else {
            try {
                $statement = $bind ? $this->self->executeInternal($sql, $bind) : @$this->self->getPdo()->query($sql);
                return $statement->fetchAll($mode);
            } catch (PDOException $exception) {
                try {
                    $this->self->close();
                    $statement = $bind
                        ? $this->self->executeInternal($sql, $bind)
                        : @$this->self->getPdo()->query(
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

    /**
     * @return void
     */
    public function close()
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

    /**
     * @return float|null
     */
    public function getLastHeartbeat()
    {
        return $this->last_heartbeat;
    }

    public function begin()
    {
        if ($this->readonly) {
            throw new ReadonlyException(['`:uri` is readonly, transaction begin failed', 'uri' => $this->uri]);
        }

        try {
            return $this->in_transaction = $this->self->getPdo()->beginTransaction();
        } catch (PDOException $exception) {
            try {
                $this->self->close();
                return $this->in_transaction = $this->self->getPdo()->beginTransaction();
            } catch (PDOException $exception) {
                throw new DbException($exception->getMessage(), $exception->getCode(), $exception);
            }
        }
    }

    public function rollback()
    {
        $this->in_transaction = false;
        return @$this->pdo->rollBack();
    }

    public function commit()
    {
        $this->in_transaction = false;
        return @$this->pdo->commit();
    }
}