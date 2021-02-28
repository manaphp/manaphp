<?php

namespace ManaPHP\Data\Db\Connection\Adapter;

use ManaPHP\Data\Db;
use ManaPHP\Data\Db\Connection;
use PDO;

/**
 * @property-read \ManaPHP\AliasInterface $alias
 */
class Sqlite extends Connection
{
    /**
     * @param string $file
     */
    public function __construct($file)
    {
        $this->uri = $file;

        $this->dsn = 'sqlite:' . ($file[0] === '@' ? $this->alias->resolve($file) : $file);
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
        $fields = $this->self->query('PRAGMA table_info(' . $this->self->escapeIdentifier($table) . ')', null);

        $attributes = [];
        $primaryKeys = [];
        $autoIncrementAttribute = null;

        foreach ($fields as $field) {
            $fieldName = $field['name'];

            $attributes[] = $fieldName;

            if ($field['pk'] === '1') {
                $primaryKeys[] = $fieldName;
            }

            if ($field['pk'] === '1' && $field['type'] === 'INTEGER') {
                $autoIncrementAttribute = $fieldName;
            }
        }

        return [
            Db::METADATA_ATTRIBUTES         => $attributes,
            Db::METADATA_PRIMARY_KEY        => $primaryKeys,
            Db::METADATA_AUTO_INCREMENT_KEY => $autoIncrementAttribute,
        ];
    }

    /**
     * @param string $table
     *
     * @return static
     * @throws \ManaPHP\Data\Db\Exception
     */
    public function truncate($table)
    {
        $this->self->execute('DELETE' . ' FROM ' . $this->self->escapeIdentifier($table));
        $this->self->execute('DELETE' . ' FROM sqlite_sequence WHERE name=:name', ['name' => $table]);

        return $this;
    }

    /**
     * @param string $table
     *
     * @return static
     * @throws \ManaPHP\Data\Db\Exception
     */
    public function drop($table)
    {
        $this->self->execute('DROP' . ' TABLE IF EXISTS ' . $this->self->escapeIdentifier($table));

        return $this;
    }

    /**
     * @param string $schema
     *
     * @return array
     *
     * @throws \ManaPHP\Data\Db\Exception
     */
    public function getTables($schema = null)
    {
        $sql = 'SELECT' . " tbl_name FROM sqlite_master WHERE type = 'table' ORDER BY tbl_name";
        $tables = [];
        foreach ($this->self->query($sql) as $row) {
            $tables[] = $row['tbl_name'];
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

        $sql
            = /** @lang text */
            "SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM sqlite_master"
            . " WHERE type='table' AND tbl_name='$parts[0]'";

        $r = $this->self->query($sql, [], PDO::FETCH_NUM);

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
}