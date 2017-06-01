<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/20
 * Time: 22:06
 */
namespace ManaPHP\Db\Adapter;

use ManaPHP\Db;
use ManaPHP\Mvc\Model\Metadata;

/**
 * Class ManaPHP\Db\Adapter\Mysql
 *
 * @package db\adapter
 */
class Mysql extends Db
{
    /**
     * \ManaPHP\Db\Adapter constructor
     *
     * @param array|string $options
     *
     * @throws \ManaPHP\Db\Exception
     */
    public function __construct($options)
    {
        if (is_string($options)) {
            $parts = parse_url($options);
            $options = [];
            if (isset($parts['user'])) {
                $options['username'] = $parts['user'];
            }

            if (isset($parts['pass'])) {
                $options['password'] = $parts['pass'];
            }

            if (isset($parts['host'])) {
                $options['host'] = $parts['host'];
            }

            if (isset($parts['port'])) {
                $options['port'] = $parts['port'];
            }

            if (isset($parts['path'])) {
                $options['dbname'] = trim($parts['path'], '/');
            }
        } elseif (is_object($options)) {
            $options = (array)$options;
        }

        if (isset($options['options'])) {
            $this->_options = $options['options'];
        }

        if (!isset($this->_options[\PDO::MYSQL_ATTR_INIT_COMMAND])) {
            $this->_options[\PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES 'UTF8'";
        }

        $this->_username = isset($options['username']) ? $options['username'] : 'root';
        $this->_password = isset($options['password']) ? $options['password'] : '';

        if (isset($options['dsn'])) {
            $this->_dsn = $options['dsn'];
        } else {
            unset($options['username'], $options['password'], $options['options']);

            $dsn_parts = [];
            foreach ($options as $k => $v) {
                $dsn_parts[] = $k . '=' . $v;
            }
            $this->_dsn = 'mysql:' . implode(';', $dsn_parts);
        }

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
        $columns = $this->fetchAll('DESCRIBE ' . $this->_escapeIdentifier($source), [], \PDO::FETCH_NUM);

        $attributes = [];
        $primaryKeys = [];
        $nonPrimaryKeys = [];
        $autoIncrementAttribute = null;
        foreach ($columns as $column) {
            $columnName = $column[0];

            $attributes[] = $columnName;

            if ($column[3] === 'PRI') {
                $primaryKeys[] = $columnName;
            } else {
                $nonPrimaryKeys = $columnName;
            }

            if ($column[5] === 'auto_increment') {
                $autoIncrementAttribute = $columnName;
            }
        }

        $r = [
            Metadata::MODEL_ATTRIBUTES => $attributes,
            Metadata::MODEL_PRIMARY_KEY => $primaryKeys,
            Metadata::MODEL_NON_PRIMARY_KEY => $nonPrimaryKeys,
            Metadata::MODEL_IDENTITY_COLUMN => $autoIncrementAttribute,
        ];

        return $r;
    }

    /**
     * @param string $source
     *
     * @return static
     * @throws \ManaPHP\Db\Exception
     */
    public function truncateTable($source)
    {
        $this->execute('TRUNCATE TABLE ' . $this->_escapeIdentifier($source));

        return $this;
    }

    /**
     * @param string $source
     *
     * @return static
     * @throws \ManaPHP\Db\Exception
     */
    public function dropTable($source)
    {
        $this->execute('DROP TABLE IF EXISTS ' . $this->_escapeIdentifier($source));

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
            $sql = 'SHOW TABLES FROM `' . $this->_escapeIdentifier($schema) . '`';
        } else {
            $sql = 'SHOW TABLES';
        }

        $tables = [];
        foreach ($this->fetchAll($sql, [], \PDO::FETCH_NUM) as $row) {
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
            /** @noinspection SqlDialectInspection */
            $sql = "SELECT IF(COUNT(*) > 0, 1, 0) FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_NAME`= '$parts[0]' AND `TABLE_SCHEMA` = '$parts[1]'";
        } else {
            /** @noinspection SqlDialectInspection */
            $sql = "SELECT IF(COUNT(*) > 0, 1, 0) FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_NAME` = '$parts[0]' AND `TABLE_SCHEMA` = DATABASE()";
        }

        $r = $this->fetchOne($sql, [], \PDO::FETCH_NUM);

        return $r[0] === '1';
    }

    public function buildSql($params)
    {
        $sql = '';

        if (isset($params['columns'])) {
            $sql .= 'SELECT ';

            if (isset($params['distinct'])) {
                $sql .= 'DISTINCT ';
            }

            $sql .= $params['columns'];
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
        return preg_replace('#\[([a-z_][a-z0-9_]*)\]#i', '`\\1`', $sql);
    }
}