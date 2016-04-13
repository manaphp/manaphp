<?php

namespace ManaPHP {

    use ManaPHP\Db\ConditionParser;
    use ManaPHP\Db\Exception;
    use ManaPHP\Db\PrepareEmulation;

    class Db extends Component implements DbInterface
    {
        /**
         * Descriptor used to connect to a database
         *
         * @var array
         */
        protected $_descriptor;

        /**
         * Type of database system driver is used for
         *
         * @var string
         */
        protected $_type;

        /**
         * Active SQL Statement
         *
         * @var string
         */
        protected $_sqlStatement;

        /**
         * Active SQL bound parameter variables
         *
         * @var array
         */
        protected $_sqlBindParams;

        /**
         * Active SQL Bind Types
         *
         * @var array
         */
        protected $_sqlBindTypes;

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
         * @param array $descriptor
         */
        public function __construct($descriptor)
        {
            if (!isset($descriptor['options'])) {
                $descriptor['options'] = [];
            }

            $descriptor['options'] = [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION];

            $this->_type = 'mysql';
            $this->_descriptor = $descriptor;

            $this->_connect();
        }

        /**
         * This method is automatically called in ManaPHP\Db\Adapter\Pdo constructor.
         * Call it when you need to restore a database connection
         *
         *<code>
         * //Make a connection
         * $connection = new \ManaPHP\Db\Adapter\Pdo\Mysql(array(
         *  'host' => '192.168.0.11',
         *  'username' => 'sigma',
         *  'password' => 'secret',
         *  'dbname' => 'blog',
         * ));
         *
         * //Reconnect
         * $connection->connect();
         * </code>
         *
         * @return    boolean
         */
        protected function _connect()
        {
            $descriptor = $this->_descriptor;

            $username = isset($descriptor['username']) ? $descriptor['username'] : null;
            $password = isset($descriptor['password']) ? $descriptor['password'] : null;
            $options = $descriptor['options'];
            unset($descriptor['username'], $descriptor['password'], $descriptor['options']);

            if (isset($descriptor['dsn'])) {
                $dsn = $descriptor['dsn'];
            } else {
                $dsn_parts = [];
                foreach ($descriptor as $k => $v) {
                    $dsn_parts[] = $k . '=' . $v;
                }
                $dsn = implode(';', $dsn_parts);
            }

            $this->_pdo = new \PDO($this->_type . ':' . $dsn, $username, $password, $options);
        }

        /**
         * Executes a prepared statement binding. This function uses integer indexes starting from zero
         *
         *<code>
         * $statement = $db->prepare('SELECT * FROM robots WHERE name = :name');
         * $result = $connection->executePrepared($statement, array('name' => 'mana'));
         *</code>
         *
         * @param \PDOStatement statement
         * @param array         $bindParams
         * @param array         $bindTypes
         *
         * @return \PDOStatement
         * @throws \ManaPHP\Db\Exception
         */
        protected function _executePrepared($statement, $bindParams, $bindTypes)
        {
            foreach ($bindParams as $parameter => $value) {
                if (is_int($parameter)) {
                    $statement->bindValue($parameter + 1, $value, $bindTypes[$parameter]);
                } else {
                    $statement->bindValue($parameter, $value, $bindTypes[$parameter]);
                }
            }

            $statement->execute();

            return $statement;
        }

        /**
         * @param $binds
         * @param $bindParams
         * @param $bindTypes
         *
         * @throws \ManaPHP\Db\Exception
         */
        protected function _parseBinds($binds, &$bindParams, &$bindTypes)
        {
            $bindParams = null;
            $bindTypes = null;

            if ($binds === null || count($binds) === 0) {
                return;
            }

            $bindParams = [];
            $bindTypes = [];

            foreach ($binds as $k => $v) {
                if (is_int($k)) {
                    $column = $k;
                } else {
                    $column = ($k[0] === ':') ? $k : (':' . $k);
                }

                if (is_scalar($v) || $v === null) {
                    $data = $v;
                    $type = null;
                } elseif (is_array($v)) {
                    if (count($v) === 1) {
                        $data = $v[0];
                        $type = null;
                    } elseif (count($v) === 2) {
                        list($data, $type) = $v;
                    } else {
                        throw new Exception('one of binds has invalid values: ' . $column);
                    }
                } else {
                    throw new Exception('one of binds has invalid values: ' . $column);
                }

                if (!is_scalar($data) && $data !== null) {
                    throw new Exception('one of binds has invalid values: ' . $column);
                }

                if ($type === null) {
                    if (is_string($data)) {
                        $type = \PDO::PARAM_STR;
                    } elseif (is_int($data)) {
                        $type = \PDO::PARAM_INT;
                    } elseif (is_bool($data)) {
                        $type = \PDO::PARAM_BOOL;
                    } elseif ($data === null) {
                        $type = \PDO::PARAM_NULL;
                    } else {
                        $type = \PDO::PARAM_STR;
                    }
                }

                $bindParams[$column] = $data;
                $bindTypes[$column] = $type;
            }
        }

        /**
         * Sends SQL statements to the database server returning the success state.
         * Use this method only when the SQL statement sent to the server is returning rows
         *
         *<code>
         *    //Querying data
         *    $resultset = $connection->query("SELECT * FROM robots WHERE type='mechanical'");
         *    $resultset = $connection->query("SELECT * FROM robots WHERE type=?", array("mechanical"));
         *</code>
         *
         * @param string $sql
         * @param array  $binds
         * @param int    $fetchMode
         *
         * @return \PdoStatement
         * @throws \ManaPHP\Db\Exception
         */
        public function query($sql, $binds = null, $fetchMode = \PDO::FETCH_ASSOC)
        {
            $this->_parseBinds($binds, $bindParams, $bindTypes);

            $this->_sqlStatement = $sql;
            $this->_sqlBindParams = $bindParams;
            $this->_sqlBindTypes = $bindTypes;

            if ($this->fireEvent('db:beforeQuery') === false) {
                return false;
            }

            try {
                if ($bindParams !== null) {
                    $statement = $this->_pdo->prepare($sql);
                    $statement = $this->_executePrepared($statement, $bindParams, $bindTypes);
                } else {
                    $statement = $this->_pdo->query($sql);
                }

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
         *
         *<code>
         *    //Inserting data
         *    $success = $connection->execute("INSERT INTO robots VALUES (1, 'Boy')");
         *    $success = $connection->execute("INSERT INTO robots VALUES (?, ?)", array(1, 'Boy'));
         *</code>
         *
         * @param string $sql
         * @param array  $binds
         *
         * @return int
         * @throws \ManaPHP\Db\Exception
         */
        public function execute($sql, $binds = null)
        {
            $this->_parseBinds($binds, $bindParams, $bindTypes);

            $this->_sqlStatement = $sql;
            $this->_sqlBindParams = $bindParams;
            $this->_sqlBindTypes = $bindTypes;

            $this->_affectedRows = 0;

            $this->fireEvent('db:beforeQuery');

            try {
                if ($bindParams !== null) {
                    $statement = $this->_pdo->prepare($sql);
                    $newStatement = $this->_executePrepared($statement, $bindParams, $bindTypes);
                    $this->_affectedRows = $newStatement->rowCount();
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
         * Escapes a column/table/schema name
         *
         * <code>
         * echo $connection->escapeIdentifier('my_table'); // `my_table`
         * echo $connection->escapeIdentifier(['companies', 'name']); // `companies`.`name`
         * <code>
         *
         * @param string|array identifier
         *
         * @return string
         */
        public function escapeIdentifier($identifier)
        {
            if (is_array($identifier)) {
                return '`' . $identifier[0] . '`.`' . $identifier[1] . '`';
            } else {
                return '`' . $identifier . '`';
            }
        }

        /**
         * Escapes a column/table/schema name
         *
         * <code>
         * echo $connection->escapeIdentifier('my_table'); // `my_table`
         * echo $connection->escapeIdentifier(['companies', 'name']); // `companies`.`name`
         * <code>
         *
         * @param array $identifiers
         *
         * @return string
         */
        public function _escapeIdentifiers($identifiers)
        {
            $escaped_identifiers = [];
            foreach ($identifiers as $identifier) {
                $escaped_identifiers[] = '`' . $identifier . '`';
            }

            return $escaped_identifiers;
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
         *<code>
         *    //Getting first robot
         *    $robot = $connection->fetchOne("SELECT * FROM robots");
         *    print_r($robot);
         *
         *    //Getting first robot with associative indexes only
         *    $robot = $connection->fetchOne("SELECT * FROM robots", \ManaPHP\Db::FETCH_ASSOC);
         *    print_r($robot);
         *</code>
         *
         * @param string $sql
         * @param array  $binds
         * @param int    $fetchMode
         *
         * @throws \ManaPHP\Db\Exception
         * @return array|false
         */
        public function fetchOne($sql, $binds = null, $fetchMode = \PDO::FETCH_ASSOC)
        {
            $result = $this->query($sql, $binds, $fetchMode);

            return $result->fetch();
        }

        /**
         * Dumps the complete result of a query into an array
         *
         *<code>
         *    //Getting all robots with associative indexes only
         *    $robots = $connection->fetchAll("SELECT * FROM robots", \ManaPHP\Db::FETCH_ASSOC);
         *    foreach ($robots as $robot) {
         *        print_r($robot);
         *    }
         *
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
         * @param array  $binds
         * @param int    $fetchMode
         *
         * @throws \ManaPHP\Db\Exception
         * @return array
         */
        public function fetchAll($sql, $binds = null, $fetchMode = \PDO::FETCH_ASSOC)
        {
            $result = $this->query($sql, $binds, $fetchMode);

            return $result->fetchAll();
        }

        /**
         * Inserts data into a table using custom SQL syntax
         *
         * <code>
         * //Inserting a new robot
         * $success = $connection->insert(
         *     "robots",
         *     array("Boy", 1952),
         *     array("name", "year")
         * );
         *
         * //Next SQL sentence is sent to the database system
         * INSERT INTO `robots` (`name`, `year`) VALUES ("boy", 1952);
         * </code>
         *
         * @param    string $table
         * @param    array  $columnValues
         *
         * @return    boolean
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

            return $this->execute($sql, $columnValues) === 1;
        }

        /**
         * Updates data on a table using custom SQL syntax
         *
         * <code>
         * //Updating existing robot
         * $success = $connection->update(
         *     "robots",
         *     array("name"),
         *     array("New Boy"),
         *     "id = 101"
         * );
         *
         * //Next SQL sentence is sent to the database system
         * UPDATE `robots` SET `name` = "boy" WHERE id = 101
         * </code>
         *
         * @param    string       $table
         * @param    array        $columnValues
         * @param    string|array $conditions
         * @param    array        $binds
         *
         * @return    int|false
         * @throws \ManaPHP\Db\Exception
         */
        public function update($table, $columnValues, $conditions, $binds = null)
        {
            $escapedTable = "`$table`";

            if (count($columnValues) === 0) {
                throw new Exception('Unable to update ' . $table . ' without data');
            }

            $where = (new ConditionParser())->parse($conditions, $conditionBinds);
            $binds = $binds ? array_merge($conditionBinds, $binds) : $conditionBinds;

            $setColumns = [];
            foreach ($columnValues as $k => $v) {
                $setColumns[] = "`$k`=:$k";
                $binds[$k] = $v;
            }

            $updateColumns = implode(',', $setColumns);
            $updateSql = /** @lang Text */
                "UPDATE $escapedTable SET $updateColumns WHERE  $where";

            return $this->execute($updateSql, $binds);
        }

        /**
         * Deletes data from a table using custom SQL syntax
         *
         * <code>
         * //Deleting existing robot
         * $success = $connection->delete(
         *     "robots",
         *     "id = 101"
         * );
         *
         * //Next SQL sentence is generated
         * DELETE FROM `robots` WHERE `id` = 101
         * </code>
         *
         * @param  string       $table
         * @param  string|array $conditions
         * @param  array        $binds
         *
         * @return boolean
         * @throws \ManaPHP\Db\Exception
         */
        public function delete($table, $conditions, $binds = null)
        {
            $where = (new ConditionParser())->parse($conditions, $conditionBinds);

            $sql = /**@lang Text */
                "DELETE FROM `$table` WHERE " . $where;

            if ($binds === null) {
                $binds = $conditionBinds;
            } else {
                $binds = array_merge($conditionBinds, $binds);
            }

            return $this->execute($sql, $binds);
        }

        /**
         * Appends a LIMIT clause to $sqlQuery argument
         *
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
        public function limit($sql, $number, $offset = null)
        {
            return $sql . ' LIMIT ' . $number . ($offset === null ? '' : (' OFFSET ' . $offset));
        }

        /**
         * Active SQL statement in the object
         *
         * @return string
         */
        public function getSQLStatement()
        {
            return $this->_sqlStatement;
        }

        /**
         * Active SQL statement in the object with replace the bind with value
         *
         * @param int $preservedStrLength
         *
         * @return string
         * @throws \ManaPHP\Db\Exception
         */
        public function getEmulatePrepareSQLStatement($preservedStrLength = -1)
        {
            return (new PrepareEmulation($this->_pdo))->emulate($this->_sqlStatement, $this->_sqlBindParams,
                $this->_sqlBindTypes, $preservedStrLength);
        }

        /**
         * Active SQL statement in the object
         *
         * @return array
         */
        public function getSQLBindParams()
        {
            return $this->_sqlBindParams;
        }

        /**
         * Active SQL statement in the object
         *
         * @return array
         */
        public function getSQLBindTypes()
        {
            return $this->_sqlBindTypes;
        }

        /**
         * Starts a transaction in the connection
         *
         * @return boolean
         * @throws \ManaPHP\Db\Exception
         */
        public function begin()
        {
            if ($this->_transactionLevel !== 0) {
                throw new Exception('There is in a active transaction already.');
            }

            $this->fireEvent('db:beginTransaction');

            $this->_transactionLevel++;

            return $this->_pdo->beginTransaction();
        }

        /**
         * Checks whether the connection is under a transaction
         *
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
         * @return boolean
         * @throws \ManaPHP\Db\Exception
         */
        public function rollback()
        {
            if ($this->_transactionLevel === 0) {
                throw new Exception('There is no active transaction');
            }

            $this->fireEvent('db:rollbackTransaction');

            $this->_transactionLevel--;

            return $this->_pdo->rollBack();
        }

        /**
         * Commits the active transaction in the connection
         *
         * @return boolean
         * @throws \ManaPHP\Db\Exception
         */
        public function commit()
        {
            if ($this->_transactionLevel === 0) {
                throw new Exception('There is no active transaction');
            }

            $this->fireEvent('db:commitTransaction');

            $this->_transactionLevel--;

            return $this->_pdo->commit();
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

        /**
         * Return internal PDO handler
         *
         * @return \PDO
         */
        public function getInternalHandler()
        {
            return $this->_pdo;
        }
    }
}
