<?php

namespace ManaPHP\Db;

use JsonSerializable;
use ManaPHP\Component;
use ManaPHP\Db\Exception as DbException;
use ManaPHP\Exception\NotSupportedException;
use PDO;
use PDOException;

abstract class Connection extends Component implements ConnectionInterface
{
    /**
     * @var string
     */
    protected $_url;

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
     * @var \PDO
     */
    protected $_pdo;

    /**
     * @var bool
     */
    protected $_in_transaction = false;

    /**
     * @var \PDOStatement[]
     */
    protected $_prepared = [];

    /**
     * @var bool
     */
    protected $_readonly = false;

    public function __construct()
    {
        $this->_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        $this->_options[PDO::ATTR_EMULATE_PREPARES] = false;
    }

    public function __clone()
    {
        $this->_pdo = null;
        $this->_in_transaction = false;
        $this->_prepared = [];
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->_url;
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
            $this->fireEvent('db:connect', $this->_dsn);
            try {
                $params = [$this->_dsn, $this->_username, $this->_password, $this->_options];
                $this->_pdo = $this->getInstance('PDO', $params);
            } catch (PDOException $e) {
                $code = $e->getCode();
                throw new ConnectionException(['connect `%s` failed: %s', $this->_dsn, $e->getMessage()], $code, $e);
            }
        }

        return $this->_pdo;
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
     * @param string $sql
     *
     * @return \PDOStatement
     */
    protected function _getPrepared($sql)
    {
        if (!isset($this->_prepared[$sql])) {
            if (count($this->_prepared) > 8) {
                array_shift($this->_prepared);
            }
            return $this->_prepared[$sql] = @$this->_getPdo()->prepare($sql);
        }
        return $this->_prepared[$sql];
    }

    /**
     * @param string $sql
     * @param array  $bind
     *
     * @return \PDOStatement
     */
    protected function _execute($sql, $bind)
    {
        $statement = $this->_getPrepared($sql);

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

        return $statement;
    }

    /**
     * @param string $sql
     * @param array  $bind
     * @param bool   $has_insert_id
     *
     * @return int
     * @throws \ManaPHP\Db\ConnectionException
     * @throws \ManaPHP\Exception\NotSupportedException
     */
    public function execute($sql, $bind = [], $has_insert_id = false)
    {
        if ($this->_readonly) {
            throw new ReadonlyException(['`:url` is readonly: => :sql ', 'url' => $this->_url, 'sql' => $sql]);
        }

        $sql = $this->replaceQuoteCharacters($sql);

        if ($this->_in_transaction) {
            try {
                $r = $bind ? $this->_execute($sql, $bind)->rowCount() : @$this->_getPdo()->exec($sql);
                return $has_insert_id ? $this->_getPdo()->lastInsertId() : $r;
            } catch (PDOException $exception) {
            }
        } else {
            try {
                $r = $bind ? $this->_execute($sql, $bind)->rowCount() : @$this->_getPdo()->exec($sql);
                return $has_insert_id ? $this->_getPdo()->lastInsertId() : $r;
            } catch (PDOException $exception) {
                try {
                    $this->close();
                    $r = $bind ? $this->_execute($sql, $bind)->rowCount() : @$this->_getPdo()->exec($sql);
                    return $has_insert_id ? $this->_getPdo()->lastInsertId() : $r;
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
        $sql = $this->replaceQuoteCharacters($sql);

        if ($this->_in_transaction) {
            try {
                $statement = $bind ? $this->_execute($sql, $bind) : @$this->_getPdo()->query($sql);
                return $statement->fetchAll($mode);
            } catch (PDOException $exception) {
            }
        } else {
            try {
                $statement = $bind ? $this->_execute($sql, $bind) : @$this->_getPdo()->query($sql);
                return $statement->fetchAll($mode);
            } catch (PDOException $exception) {
                try {
                    $this->close();
                    $statement = $bind ? $this->_execute($sql, $bind) : @$this->_getPdo()->query($sql);
                    return $statement->fetchAll($mode);
                } catch (PDOException $exception) {
                }
            }
        }

        $bind_str = json_stringify($bind, JSON_PRETTY_PRINT);
        throw new DbException(["%s =>\r\n SQL: %s\r\n BIND: %s", $exception->getMessage(), $sql, $bind_str]);
    }

    public function close()
    {
        if ($this->_pdo) {
            $this->_pdo = null;
            $this->_prepared = [];
            if ($this->_in_transaction) {
                $this->_in_transaction = false;
                $this->fireEvent('db:abnormal');
            }
        }
    }

    public function begin()
    {
        if ($this->_readonly) {
            throw new ReadonlyException(['`:url` is readonly, transaction begin failed', 'url' => $this->_url]);
        }

        try {
            return $this->_in_transaction = $this->_getPdo()->beginTransaction();
        } catch (PDOException $exception) {
            try {
                $this->close();
                return $this->_in_transaction = $this->_getPdo()->beginTransaction();
            } catch (PDOException $exception) {
                throw new DbException($exception->getMessage(), $exception->getCode(), $exception);
            }
        }
    }

    public function rollback()
    {
        $this->_in_transaction = false;
        return @$this->_pdo->rollBack();
    }

    public function commit()
    {
        $this->_in_transaction = false;
        return @$this->_pdo->commit();
    }
}