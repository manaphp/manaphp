<?php

namespace ManaPHP\Data\Db\Connection\Adapter;

use ManaPHP\Data\Db;
use ManaPHP\Data\Db\Connection;
use ManaPHP\Data\Db\SqlFragmentable;
use ManaPHP\Exception\DsnFormatException;
use ManaPHP\Exception\InvalidArgumentException;
use PDO;

class Mysql extends Connection
{
    /**
     * @var string
     */
    protected $charset = 'UTF8';

    /**
     * @param string $uri
     */
    public function __construct($uri = 'mysql://root@localhost/test?charset=utf8')
    {
        $this->uri = $uri;

        $parts = parse_url($uri);

        if ($parts['scheme'] !== 'mysql') {
            throw new DsnFormatException(['`%s` is invalid, `%s` scheme is not recognized', $uri, $parts['scheme']]);
        }

        $this->username = $parts['user'] ?? 'root';
        $this->password = $parts['pass'] ?? '';

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
                $this->charset = $query['charset'];
            }

            if (!MANAPHP_COROUTINE_ENABLED && isset($query['persistent'])) {
                $this->options[PDO::ATTR_PERSISTENT] = $query['persistent'] === '1';
            }

            if (isset($query['timeout'])) {
                $this->options[PDO::ATTR_TIMEOUT] = (int)$query['timeout'];
            }

            if (isset($query['user'])) {
                $this->username = $query['user'];
            }

            if (isset($query['password'])) {
                $this->password = $query['password'];
            }

            if (isset($query['db'])) {
                $dsn['dbname'] = $query['db'];
            }

            if (isset($query['readonly']) && $query['readonly'] !== '0') {
                $this->readonly = true;
            }

            if (isset($query['emulate_prepares']) && $query['emulate_prepares'] !== '0') {
                $this->emulate_prepares = true;
            }
        }

        $this->options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES '{$this->charset}'";

        $dsn_parts = [];
        foreach ($dsn as $k => $v) {
            $dsn_parts[] = $k . '=' . $v;
        }
        $this->dsn = 'mysql:' . implode(';', $dsn_parts);

        parent::__construct();
    }

    /**
     * @param string $table
     *
     * @return array
     * @throws \ManaPHP\Data\Db\Exception
     */
    public function getMetadata($table)
    {
        $fields = $this->query('DESCRIBE ' . $this->escapeIdentifier($table), [], PDO::FETCH_NUM);

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
            Db::METADATA_ATTRIBUTES          => $attributes,
            Db::METADATA_PRIMARY_KEY         => $primaryKeys,
            Db::METADATA_AUTO_INCREMENT_KEY  => $autoIncrementAttribute,
            Db::METADATA_INT_TYPE_ATTRIBUTES => $intTypes,
        ];
    }

    /**
     * @param string $table
     *
     * @throws \ManaPHP\Data\Db\Exception
     */
    public function truncate($table)
    {
        $this->execute('TRUNCATE' . ' TABLE ' . $this->escapeIdentifier($table));
    }

    /**
     * @param string $table
     *
     * @throws \ManaPHP\Data\Db\Exception
     */
    public function drop($table)
    {
        $this->execute('DROP' . ' TABLE IF EXISTS ' . $this->escapeIdentifier($table));
    }

    /**
     * @param string $schema
     *
     * @return array
     * @throws \ManaPHP\Data\Db\Exception
     */
    public function getTables($schema = null)
    {
        if ($schema) {
            $sql = 'SHOW FULL TABLES FROM `' . $this->escapeIdentifier($schema) . '` WHERE Table_Type != "VIEW"';
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
     * @param string $table
     *
     * @return bool
     * @throws \ManaPHP\Data\Db\Exception
     */
    public function tableExists($table)
    {
        $parts = explode('.', str_replace('[]`', '', $table));

        if (count($parts) === 2) {
            $sql
                = /**@lang text */
                'SELECT IF(COUNT(*) > 0, 1, 0) FROM `INFORMATION_SCHEMA`.`TABLES`'
                . " WHERE `TABLE_NAME`= '$parts[0]' AND `TABLE_SCHEMA` = '$parts[1]'";
        } else {
            $sql
                = /** @lang text */
                'SELECT IF(COUNT(*) > 0, 1, 0) FROM `INFORMATION_SCHEMA`.`TABLES`'
                . " WHERE `TABLE_NAME` = '$parts[0]' AND `TABLE_SCHEMA` = DATABASE()";
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
        return str_contains($sql, '[') ? preg_replace(/**@lang text */ '#\[([a-z_]\w*)\]#i', '`\\1`', $sql) : $sql;
    }

    /**
     * @param string  $table
     * @param array[] $records
     *
     * @return int
     * @throws \ManaPHP\Data\Db\Exception
     */
    public function bulkInsert($table, $records)
    {
        if (!$records) {
            throw new InvalidArgumentException(['Unable to insert into :table table without data', 'table' => $table]);
        }

        $fields = array_keys($records[0]);
        $insertedFields = '[' . implode('],[', $fields) . ']';

        $pdo = $this->getPdo();

        $rows = [];
        foreach ($records as $record) {
            $row = [];
            foreach ($record as $field => $value) {
                $row[] = is_string($value) ? $pdo->quote($value) : $value;
            }

            $rows[] = '(' . implode(',', $row) . ')';
        }

        $sql
            = /**@lang text */
            "INSERT INTO {$this->escapeIdentifier($table)} ($insertedFields) VALUES " . implode(', ', $rows);

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

        $sql
            = /** @lang text */
            "INSERT INTO {$this->escapeIdentifier($table)}($insertFieldsSql)"
            . " VALUES($insertValuesSql) ON DUPLICATE KEY UPDATE $updateFieldsSql";

        return $this->execute('insert', $sql, $bind);
    }
}