<?php
namespace ManaPHP\Db\Adapter;

use ManaPHP\Db;

/**
 * Class ManaPHP\Db\Adapter\Sqlite
 *
 * @package db\adapter
 */
class Sqlite extends Db
{
    /**
     * Sqlite constructor.
     *
     * @param string $file
     *
     * @throws \ManaPHP\Db\Exception
     */
    public function __construct($file)
    {
        $this->_dsn = 'sqlite:' . ($file[0] === '@' ? $this->alias->resolve($file) : $file);
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
        $fields = $this->fetchAll('PRAGMA table_info(' . $this->_escapeIdentifier($source) . ')', null, \PDO::FETCH_ASSOC);

        $attributes = [];
        $primaryKeys = [];
        $nonPrimaryKeys = [];
        $autoIncrementAttribute = null;

        foreach ($fields as $field) {
            $fieldName = $field['name'];

            $attributes[] = $fieldName;

            if ($field['pk'] === '1') {
                $primaryKeys[] = $fieldName;
            } else {
                $nonPrimaryKeys[] = $fieldName;
            }

            if ($field['pk'] === '1' && $field['type'] === 'INTEGER') {
                $autoIncrementAttribute = $fieldName;
            }
        }

        $r = [
            self::METADATA_ATTRIBUTES => $attributes,
            self::METADATA_PRIMARY_KEY => $primaryKeys,
            self::METADATA_NON_PRIMARY_KEY => $nonPrimaryKeys,
            self::METADATA_IDENTITY_FIELD => $autoIncrementAttribute,
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
        $this->execute('DELETE ' . 'FROM ' . $this->_escapeIdentifier($source));
        $this->execute('DELETE' . ' FROM sqlite_sequence WHERE name=:name', ['name' => $source]);

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
        $sql = "SELECT tbl_name FROM sqlite_master WHERE type = 'table' ORDER BY tbl_name";
        $tables = [];
        foreach ($this->fetchAll($sql, [], \PDO::FETCH_ASSOC) as $row) {
            $tables[] = $row['tbl_name'];
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

        $sql = "SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM sqlite_master WHERE type='table' AND tbl_name='$parts[0]'";

        $r = $this->fetchOne($sql, [], \PDO::FETCH_NUM);

        return $r[0] === '1';
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
        return preg_replace('#\[([a-z_][a-z0-9_]*)\]#i', '`\\1`', $sql);
    }
}