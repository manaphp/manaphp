<?php
namespace ManaPHP\Db;

use ManaPHP\Component;
use ManaPHP\Db\Exception as DbException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\NotSupportedException;

abstract class Connection extends Component implements ConnectionInterface
{
    /**
     * @var string
     */
    protected $_uri;

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
     * Current transaction level
     *
     * @var int
     */
    protected $_transaction_level = 0;

    /**
     * @var \PDOStatement[]
     */
    protected $_prepared = [];

    /**
     * @var int
     */
    protected $_heartbeat = 60;

    /**
     * @var float
     */
    protected $_last_heartbeat;

    public function __construct()
    {
        $this->_options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;
        $this->_options[\PDO::ATTR_EMULATE_PREPARES] = false;
    }

    public function __clone()
    {
        $this->_pdo = null;
        $this->_transaction_level = 0;
        $this->_prepared = [];
        $this->_last_heartbeat = null;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->_uri;
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
            try {
                $this->_pdo = @new \PDO($this->_dsn, $this->_username, $this->_password, $this->_options);
            } catch (\PDOException $e) {
                throw new ConnectionException(['connect `:dsn` failed: :message', 'message' => $e->getMessage(), 'dsn' => $this->_dsn], $e->getCode(), $e);
            }

            if (!isset($this->_options[\PDO::ATTR_PERSISTENT]) || !$this->_options[\PDO::ATTR_PERSISTENT]) {
                $this->_last_heartbeat = microtime(true);
                return $this->_pdo;
            }
        }

        if ($this->_transaction_level === 0 && microtime(true) - $this->_last_heartbeat >= $this->_heartbeat && !$this->_ping()) {
            $this->close();
            $this->logger->info(['reconnect to `:dsn`', 'dsn' => $this->_dsn], 'db.reconnect');
            try {
                $this->_pdo = @new \PDO($this->_dsn, $this->_username, $this->_password, $this->_options);
            } catch (\PDOException $e) {
                throw new ConnectionException(['connect `:dsn` failed: :message', 'message' => $e->getMessage(), 'dsn' => $this->_dsn], $e->getCode(), $e);
            }
        }

        $this->_last_heartbeat = microtime(true);

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
     * @param array  $bind
     *
     * @return \PDOStatement
     */
    protected function _execute($sql, $bind)
    {
        if (!isset($this->_prepared[$sql])) {
            if (count($this->_prepared) > 8) {
                array_shift($this->_prepared);
            }
            $this->_prepared[$sql] = @$this->_getPdo()->prepare($sql);
        }
        $statement = $this->_prepared[$sql];

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
     * @param string $sql
     *
     * @return string
     */
    abstract protected function _replaceQuoteCharacters($sql);

    /**
     * @param string $sql
     * @param array  $bind
     * @param bool   $has_insert_id
     *
     * @return int
     * @throws \ManaPHP\Db\ConnectionException
     * @throws \ManaPHP\Exception\InvalidValueException
     * @throws \ManaPHP\Exception\NotSupportedException
     */
    public function execute($sql, $bind = [], $has_insert_id = false)
    {
        $sql = $this->_replaceQuoteCharacters($sql);

        try {
            $r = $bind ? $this->_execute($sql, $bind)->rowCount() : @$this->_getPdo()->exec($sql);
            if ($has_insert_id) {
                return $this->_getPdo()->lastInsertId();
            } else {
                return $r;
            }
        } catch (\PDOException $e) {
            throw new DbException([
                ':message => ' . PHP_EOL . 'SQL: ":sql"' . PHP_EOL . ' BIND: :bind',
                'message' => $e->getMessage(),
                'sql' => $sql,
                'bind' => json_encode($bind, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            ]);
        }
    }

    /**
     * @param string $sql
     * @param array  $bind
     * @param int    $fetchMode
     * @param bool   $useMaster
     *
     * @return array
     */
    public function query($sql, $bind = [], $fetchMode = \PDO::FETCH_ASSOC, $useMaster = false)
    {
        $sql = $this->_replaceQuoteCharacters($sql);

        try {
            $statement = $bind ? $this->_execute($sql, $bind) : @$this->_getPdo()->query($sql);
        } catch (\PDOException $e) {
            $failed = true;

            if ($this->_transaction_level === 0 && !$this->_ping()) {
                try {
                    $this->close();
                    $statement = $bind ? $this->_execute($sql, $bind) : @$this->_getPdo()->query($sql);
                    $failed = false;
                } catch (\PDOException $e) {

                }
            }

            if ($failed) {
                throw new DbException([
                    ':message => ' . PHP_EOL . 'SQL: ":sql"' . PHP_EOL . ' BIND: :bind',
                    'message' => $e->getMessage(),
                    'sql' => $sql,
                    'bind' => json_encode($bind, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                ], 0, $e);
            }
        }

        return $statement->fetchAll($fetchMode);
    }

    public function close()
    {
        if ($this->_pdo) {
            $this->_pdo = null;
            $this->_prepared = [];
            $this->_last_heartbeat = null;
            if ($this->_transaction_level !== 0) {
                $this->_transaction_level = 0;
                $this->_pdo->rollBack();
                $this->logger->warn('transaction is not close correctly', 'db.transaction.abnormal');
            }
        }
    }

    public function beginTransaction()
    {
        return $this->_getPdo()->beginTransaction();
    }

    public function rollBack()
    {
        return $this->_getPdo()->rollBack();
    }

    public function commit()
    {
        return $this->_getPdo()->rollBack();
    }
}