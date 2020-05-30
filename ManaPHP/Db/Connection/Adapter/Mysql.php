<?php

namespace ManaPHP\Db\Connection\Adapter;

use ManaPHP\Db;
use ManaPHP\Db\Connection;
use ManaPHP\Db\SqlFragmentable;
use ManaPHP\Exception\DsnFormatException;
use ManaPHP\Exception\InvalidArgumentException;
use PDO;

class Mysql extends Connection
{
    /**
     * @var string
     */
    protected $_charset = 'UTF8';

    /**
     * \ManaPHP\Db\Adapter constructor
     *
     * @param string $url
     */
    public function __construct($url = 'mysql://root@localhost/test?charset=utf8')
    {
        $this->_url = $url;

        $parts = parse_url($url);

        if ($parts['scheme'] !== 'mysql') {
            throw new DsnFormatException(['`:url` is invalid, `:scheme` scheme is not recognized', 'url' => $url, 'scheme' => $parts['scheme']]);
        }

        $this->_username = $parts['user'] ?? 'root';
        $this->_password = $parts['pass'] ?? '';

        $dsn = [];

        if (isset($parts['host'])) {
            $dsn['host'] = $parts['host'];
        }

        if (isset($parts['port'])) {
            $dsn['port'] = $parts['port'];
        }

        if (isset($parts['path'])) {
            $db = trim($parts['path'], '/');
            if ($db !== '') {
                $dsn['dbname'] = $db;
            }
        }

        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);

            if (isset($query['charset'])) {
                $this->_charset = $query['charset'];
            }

            if (!MANAPHP_COROUTINE_ENABLED && isset($query['persistent'])) {
                $this->_options[PDO::ATTR_PERSISTENT] = $query['persistent'] === '1';
            }

            if (isset($query['timeout'])) {
                $this->_options[PDO::ATTR_TIMEOUT] = (int)$query['timeout'];
            }

            if (isset($query['user'])) {
                $this->_username = $query['user'];
            }

            if (isset($query['password'])) {
                $this->_password = $query['password'];
            }

            if (isset($query['db'])) {
                $dsn['dbname'] = $query['db'];
            }

            if (isset($query['readonly']) && $query['readonly'] !== '0') {
                $this->_readonly = true;
            }
        }

        $this->_options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES '{$this->_charset}'";

        $dsn_parts = [];
        foreach ($dsn as $k => $v) {
            $dsn_parts[] = $k . '=' . $v;
        }
        $this->_dsn = 'mysql:' . implode(';', $dsn_parts);

        parent::__construct();
    }

    /**
     * @param string $source
     *
     * @return array
     * @throws \ManaPHP\Db\Exception
     */
    public function getMetadata($source)
    {
        $fields = $this->query('DESCRIBE ' . $this->_escapeIdentifier($source), [], PDO::FETCH_NUM);

        $attributes = [];
        $primaryKeys = [];
        $autoIncrementAttribute = null;
        $intTypes = [];

        foreach ($fields as $field) {
            $fieldName = $field[0];

            $attributes[] = $fieldName;

            if ($field[3] === 'PRI') {
                $primaryKeys[] = $fieldName;
            }

            if ($field[5] === 'auto_increment') {
                $autoIncrementAttribute = $fieldName;
            }

            $type = $field[1];
            if (str_contains($type, 'int')) {
                $intTypes[] = $fieldName;
            }
        }

        return [
            Db::METADATA_ATTRIBUTES => $attributes,
            Db::METADATA_PRIMARY_KEY => $primaryKeys,
            Db::METADATA_AUTO_INCREMENT_KEY => $autoIncrementAttribute,
            Db::METADATA_INT_TYPE_ATTRIBUTES => $intTypes,
        ];
    }

    /**
     * @param string $source
     *
     * @return static
     * @throws \ManaPHP\Db\Exception
     */
    public function truncate($source)
    {
        $this->execute('TRUNCATE' . ' TABLE ' . $this->_escapeIdentifier($source));

        return $this;
    }

    /**
     * @param string $source
     *
     * @return static
     * @throws \ManaPHP\Db\Exception
     */
    public function drop($source)
    {
        $this->execute('DROP' . ' TABLE IF EXISTS ' . $this->_escapeIdentifier($source));

        return $this;
    }

    /**
     * @param string $schema
     *
     * @return array
     * @throws \ManaPHP\Db\Exception
     */
    public function getTables($schema = null)
    {
        if ($schema) {
            $sql = 'SHOW FULL TABLES FROM `' . $this->_escapeIdentifier($schema) . '` WHERE Table_Type != "VIEW"';
        } else {
            $sql = 'SHOW FULL TABLES WHERE Table_Type != "VIEW"';
        }

        $tables = [];
        foreach ($this->query($sql, [], PDO::FETCH_NUM) as $row) {
            $tables[] = $row[0];
        }

        return $tables;
    }

    /**
     * @param string $source
     *
     * @return bool
     * @throws \ManaPHP\Db\Exception
     */
    public function tableExists($source)
    {
        $parts = explode('.', str_replace('[]`', '', $source));

        if (count($parts) === 2) {
            $sql = 'SELECT' . " IF(COUNT(*) > 0, 1, 0) FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_NAME`= '$parts[0]' AND `TABLE_SCHEMA` = '$parts[1]'";
        } else {
            $sql = 'SELECT' . " IF(COUNT(*) > 0, 1, 0) FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_NAME` = '$parts[0]' AND `TABLE_SCHEMA` = DATABASE()";
        }

        $r = $this->query($sql, [], PDO::FETCH_NUM);

        return $r && $r[0] === '1';
    }

    public function buildSql($params)
    {
        $sql = '';

        if (isset($params['fields'])) {
            $sql .= 'SELECT ';

            if (isset($params['distinct'])) {
                $sql .= 'DISTINCT ';
            }

            $sql .= $params['fields'];
        }

        if (isset($params['from'])) {
            $sql .= ' FROM ' . $params['from'];
        }

        if (isset($params['join'])) {
            $sql .= $params['join'];
        }

        if (isset($params['where'])) {
            $sql .= ' WHERE ' . $params['where'];
        }

        if (isset($params['group'])) {
            $sql .= ' GROUP BY ' . $params['group'];
        }

        if (isset($params['having'])) {
            $sql .= ' HAVING ' . $params['having'];
        }

        if (isset($params['order'])) {
            $sql .= ' ORDER BY ' . $params['order'];
        }

        if (isset($params['limit'])) {
            $sql .= ' LIMIT ' . $params['limit'];
        }

        if (isset($params['offset'])) {
            $sql .= ' OFFSET ' . $params['offset'];
        }

        if (isset($params['forUpdate'])) {
            $sql .= 'FOR UPDATE';
        }

        return $sql;
    }

    /**
     * @param string $sql
     *
     * @return string
     */
    public function replaceQuoteCharacters($sql)
    {
        return strpos($sql, '[') === false ? $sql : preg_replace(/**@lang text */ '#\[([a-z_]\w*)\]#i', '`\\1`', $sql);
    }

    /**
     * @param string  $table
     * @param array[] $records
     *
     * @return int
     * @throws \ManaPHP\Db\Exception
     */
    public function bulkInsert($table, $records)
    {
        if (!$records) {
            throw new InvalidArgumentException(['Unable to insert into :table table without data', 'table' => $table]);
        }

        $fields = array_keys($records[0]);
        $insertedFields = '[' . implode('],[', $fields) . ']';

        $pdo = $this->_getPdo();

        $rows = [];
        foreach ($records as $record) {
            $row = [];
            foreach ($record as $field => $value) {
                $row[] = is_string($value) ? $pdo->quote($value) : $value;
            }

            $rows[] = '(' . implode(',', $row) . ')';
        }

        $sql = 'INSERT' . ' INTO ' . $this->_escapeIdentifier($table) . " ($insertedFields) VALUES " . implode(', ', $rows);

        return $this->execute($sql);
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

        if (!$updateFieldValues) {
            $updateFieldValues = $insertFieldValues;
        }

        $fields = array_keys($insertFieldValues);

        $bind = $insertFieldValues;
        $updates = [];
        foreach ($updateFieldValues as $k => $v) {
            $field = is_string($k) ? $k : $v;
            if ($primaryKey === $field) {
                continue;
            }

            if (is_int($k)) {
                $updates[] = "[$field]=:{$field}_dku";
                $bind["{$field}_dku"] = $insertFieldValues[$field];
            } elseif ($v instanceof SqlFragmentable) {
                $v->setField($k);
                $updates[] = $v->getSql();
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $bind = array_merge($bind, $v->getBind());
            } else {
                $updates[] = $v;
            }
        }

        $insertFieldsSql = '[' . implode('],[', $fields) . ']';
        $insertValuesSql = ':' . implode(',:', $fields);

        $updateFieldsSql = implode(',', $updates);

        $sql = 'INSERT' . " INTO {$this->_escapeIdentifier($table)}($insertFieldsSql) VALUES($insertValuesSql) ON DUPLICATE KEY UPDATE $updateFieldsSql";

        return $this->execute('insert', $sql, $bind);
    }
}