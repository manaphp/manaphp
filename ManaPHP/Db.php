<?php

namespace ManaPHP;

use ManaPHP\Db\Exception;
use ManaPHP\Utility\Text;

abstract class Db extends Component implements DbInterface
{
    /**
     * @var array
     */
    protected $_options;

    /**
     * Type of database system driver is used for
     *
     * @var string
     */
    protected $_type;

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
     * \ManaPHP\Db\Adapter constructor
     *
     * @param array $options
     */
    public function __construct($options)
    {
        if (is_object($options)) {
            $options = (array)$options;
        }

        if (!isset($options['options'])) {
            $options['options'] = [];
        }
        $this->_options = $options['options'];

        $this->_options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;

        $this->_username = isset($options['username']) ? $options['username'] : null;
        $this->_password = isset($options['password']) ? $options['password'] : null;
        unset($options['username'], $options['password'], $options['options']);

        if (isset($options['dsn'])) {
            $this->_dsn = $options['dsn'];
        } else {
            $dsn_parts = [];
            foreach ($options as $k => $v) {
                $dsn_parts[] = $k . '=' . $v;
            }
            $this->_dsn = implode(';', $dsn_parts);
        }

        $this->_pdo = new \PDO($this->_type . ':' . $this->_dsn, $this->_username, $this->_password, $this->_options);
    }

    /**
     * Executes a prepared statement binding. This function uses integer indexes starting from zero
     *<code>
     * $statement = $db->prepare('SELECT * FROM robots WHERE name = :name');
     * $result = $connection->executePrepared($statement, array('name' => 'mana'));
     *</code>
     *
     * @param \PDOStatement $statement
     * @param array         $bind
     *
     * @return \PDOStatement
     * @throws \ManaPHP\Db\Exception
     */
    protected function _executePrepared($statement, $bind)
    {
        foreach ($bind as $parameter => $value) {
            if (is_string($value)) {
                $type = \PDO::PARAM_STR;
            } elseif (is_int($value)) {
                $type = \PDO::PARAM_INT;
            } elseif (is_bool($value)) {
                $type = \PDO::PARAM_BOOL;
            } elseif ($value === null) {
                $type = \PDO::PARAM_NULL;
            } else {
                throw new Exception("The type of parameter of '$parameter' is not support: " . gettype($value));
            }

            if (is_int($parameter)) {
                $statement->bindValue($parameter + 1, $value, $type);
            } else {
                if ($parameter[0] === ':') {
                    throw new Exception("Bind does not require started with ':' for parameter: " . $parameter);
                }

                $statement->bindValue(':' . $parameter, $value, $type);
            }
        }

        $statement->execute();

        return $statement;
    }

    /**
     * Sends SQL statements to the database server returning the success state.
     * Use this method only when the SQL statement sent to the server is returning rows
     *<code>
     *    //Querying data
     *    $resultset = $connection->query("SELECT * FROM robots WHERE type='mechanical'");
     *    $resultset = $connection->query("SELECT * FROM robots WHERE type=?", array("mechanical"));
     *</code>
     *
     * @param string $sql
     * @param array  $bind
     * @param int    $fetchMode
     *
     * @return \PdoStatement
     * @throws \ManaPHP\Db\Exception
     */
    public function query($sql, $bind = [], $fetchMode = \PDO::FETCH_ASSOC)
    {
        $this->_sql = $sql;
        $this->_bind = $bind;
        $this->_affectedRows = 0;

        $this->fireEvent('db:beforeQuery');

        try {
            if (count($bind) !== 0) {
                $statement = $this->_pdo->prepare($sql);
                $statement = $this->_executePrepared($statement, $bind);
            } else {
                $statement = $this->_pdo->query($sql);
            }

            $this->_affectedRows = $statement->rowCount();
            $statement->setFetchMode($fetchMode);
        } catch (\PDOException $e) {
            throw new Exception($e->getMessage());
        }

        $this->fireEvent('db:afterQuery');

        return $statement;
    }

    /**
     * Sends SQL statements to the database server returning the success state.
     * Use this method only when the SQL statement sent to the server does n't return any rows
     *<code>
     *    //Inserting data
     *    $success = $connection->execute("INSERT INTO robots VALUES (1, 'Boy')");
     *    $success = $connection->execute("INSERT INTO robots VALUES (?, ?)", array(1, 'Boy'));
     *</code>
     *
     * @param string $sql
     * @param array  $bind
     *
     * @return int
     * @throws \ManaPHP\Db\Exception
     */
    public function execute($sql, $bind = [])
    {
        $this->_sql = $sql;
        $this->_bind = $bind;

        $this->_affectedRows = 0;

        $this->fireEvent('db:beforeQuery');

        try {
            if (count($bind) !== 0) {
                $statement = $this->_executePrepared($this->_pdo->prepare($sql), $bind);
                $this->_affectedRows = $statement->rowCount();
            } else {
                $this->_affectedRows = $this->_pdo->exec($sql);
            }
        } catch (\PDOException $e) {
            throw new Exception($e->getMessage());
        }

        if (is_int($this->_affectedRows)) {
            $this->fireEvent('db:afterQuery');
        }

        return $this->_affectedRows;
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
     *<code>
     *    //Getting first robot
     *    $robot = $connection->fetchOne("SELECT * FROM robots");
     *    print_r($robot);
     *    //Getting first robot with associative indexes only
     *    $robot = $connection->fetchOne("SELECT * FROM robots", \ManaPHP\Db::FETCH_ASSOC);
     *    print_r($robot);
     *</code>
     *
     * @param string $sql
     * @param array  $bind
     * @param int    $fetchMode
     *
     * @throws \ManaPHP\Db\Exception
     * @return array|false
     */
    public function fetchOne($sql, $bind = [], $fetchMode = \PDO::FETCH_ASSOC)
    {
        $result = $this->query($sql, $bind, $fetchMode);

        return $result->fetch();
    }

    /**
     * Dumps the complete result of a query into an array
     *<code>
     *    //Getting all robots with associative indexes only
     *    $robots = $connection->fetchAll("SELECT * FROM robots", \ManaPHP\Db::FETCH_ASSOC);
     *    foreach ($robots as $robot) {
     *        print_r($robot);
     *    }
     *  //Getting all robots that contains word "robot" withing the name
     *  $robots = $connection->fetchAll("SELECT * FROM robots WHERE name LIKE :name",
     *        ManaPHP\Db::FETCH_ASSOC,
     *        array('name' => '%robot%')
     *  );
     *    foreach($robots as $robot){
     *        print_r($robot);
     *    }
     *</code>
     *
     * @param string $sql
     * @param array  $bind
     * @param int    $fetchMode
     *
     * @throws \ManaPHP\Db\Exception
     * @return array
     */
    public function fetchAll($sql, $bind = [], $fetchMode = \PDO::FETCH_ASSOC)
    {
        $result = $this->query($sql, $bind, $fetchMode);

        return $result->fetchAll();
    }

    /**
     * Inserts data into a table using custom SQL syntax
     * <code>
     * //Inserting a new robot
     * $success = $connection->insert(
     *     "robots",
     *     array("Boy", 1952),
     *     array("name", "year")
     * );
     * //Next SQL sentence is sent to the database system
     * INSERT INTO `robots` (`name`, `year`) VALUES ("boy", 1952);
     * </code>
     *
     * @param    string $table
     * @param    array  $columnValues
     *
     * @return void
     * @throws \ManaPHP\Db\Exception
     */
    public function insert($table, $columnValues)
    {
        if (count($columnValues) === 0) {
            throw new Exception('Unable to insert into ' . $table . ' without data');
        }

        $escapedTable = $this->escapeIdentifier($table);
        if (array_key_exists(0, $columnValues)) {
            $insertedValues = rtrim(str_repeat('?,', count($columnValues)), ',');

            $sql = /** @lang Text */
                "INSERT INTO $escapedTable VALUES ($insertedValues)";
        } else {
            $columns = array_keys($columnValues);
            $insertedValues = ':' . implode(',:', $columns);
            $insertedColumns = '`' . implode('`,`', $columns) . '`';

            $sql = /** @lang Text */
                "INSERT INTO $escapedTable ($insertedColumns) VALUES ($insertedValues)";
        }

        $this->execute($sql, $columnValues);
    }

    /**
     * Updates data on a table using custom SQL syntax
     * <code>
     * //Updating existing robot
     * $success = $connection->update(
     *     "robots",
     *     array("name"),
     *     array("New Boy"),
     *     "id = 101"
     * );
     * //Next SQL sentence is sent to the database system
     * UPDATE `robots` SET `name` = "boy" WHERE id = 101
     * </code>
     *
     * @param    string       $table
     * @param    array        $columnValues
     * @param    string|array $conditions
     * @param    array        $bind
     *
     * @return    int
     * @throws \ManaPHP\Db\Exception
     */
    public function update($table, $columnValues, $conditions, $bind = [])
    {
        $escapedTable = "`$table`";

        if (count($columnValues) === 0) {
            throw new Exception('Unable to update ' . $table . ' without data');
        }

        if (is_string($conditions)) {
            $conditions = [$conditions];
        }

        $wheres = [];

        /** @noinspection ForeachSourceInspection */
        foreach ($conditions as $k => $v) {
            if (is_int($k)) {
                $wheres[] = Text::contains($v, ' or ', true) ? "($v)" : $v;
            } else {
                $wheres[] = "`$k`=:$k";
                $bind[$k] = $v;
            }
        }

        $setColumns = [];
        foreach ($columnValues as $k => $v) {
            $setColumns[] = "`$k`=:$k";
            $bind[$k] = $v;
        }

        $updateColumns = implode(',', $setColumns);
        $updateSql = /** @lang Text */
            "UPDATE $escapedTable SET $updateColumns WHERE " . implode(' AND ', $wheres);

        return $this->execute($updateSql, $bind);
    }

    /**
     * Deletes data from a table using custom SQL syntax
     * <code>
     * //Deleting existing robot
     * $success = $connection->delete(
     *     "robots",
     *     "id = 101"
     * );
     * //Next SQL sentence is generated
     * DELETE FROM `robots` WHERE `id` = 101
     * </code>
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
                $wheres[] = Text::contains($v, ' or ', true) ? "($v)" : $v;
            } else {
                $wheres[] = "`$k`=:$k";
                $bind[$k] = $v;
            }
        }

        $sql = /**@lang Text */
            "DELETE FROM `$table` WHERE " . implode(' AND ', $wheres);

        return $this->execute($sql, $bind);
    }

    /**
     * Appends a LIMIT clause to $sqlQuery argument
     * <code>
     *    echo $connection->limit("SELECT * FROM robots", 5);
     * </code>
     *
     * @param    string $sql
     * @param    int    $number
     * @param   int     $offset
     *
     * @return    string
     */
    public function limit($sql, $number, $offset = 0)
    {
        return $sql . ' LIMIT ' . $number . ($offset === 0 ? '' : (' OFFSET ' . $offset));
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
                return $this->_pdo->quote(substr($value, 0, $preservedStrLength) . '...');
            } else {
                return $this->_pdo->quote($value);
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
     * @throws \ManaPHP\Db\Exception
     */
    public function getEmulatedSQL($preservedStrLength = -1)
    {
        if (count($this->_bind) === 0) {
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

            return (string)strtr($this->_sql, $replaces);
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
        if ($this->_transactionLevel !== 0) {
            throw new Exception('There is in a active transaction already.');
        }

        $this->fireEvent('db:beginTransaction');

        $this->_transactionLevel++;

        if (!$this->_pdo->beginTransaction()) {
            throw new Exception('beginTransaction failed.');
        }
    }

    /**
     * Checks whether the connection is under a transaction
     *<code>
     *    $connection->begin();
     *    var_dump($connection->isUnderTransaction()); //true
     *</code>
     *
     * @return bool
     */
    public function isUnderTransaction()
    {
        return $this->_pdo->inTransaction();
    }

    /**
     * Rollbacks the active transaction in the connection
     *
     * @return void
     * @throws \ManaPHP\Db\Exception
     */
    public function rollback()
    {
        if ($this->_transactionLevel === 0) {
            throw new Exception('There is no active transaction');
        }

        $this->fireEvent('db:rollbackTransaction');

        $this->_transactionLevel--;

        if (!$this->_pdo->rollBack()) {
            throw new Exception('rollBack failed.');
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
        if ($this->_transactionLevel === 0) {
            throw new Exception('There is no active transaction');
        }

        $this->fireEvent('db:commitTransaction');

        $this->_transactionLevel--;

        if (!$this->_pdo->commit()) {
            throw new Exception('commit failed.');
        }
    }

    /**
     * Returns insert id for the auto_increment column inserted in the last SQL statement
     *
     * @return int
     */
    public function lastInsertId()
    {
        return (int)$this->_pdo->lastInsertId();
    }
}