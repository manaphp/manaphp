<?php

namespace ManaPHP\Db\Connection\Adapter;

use ManaPHP\Db;
use ManaPHP\Db\Connection;
use ManaPHP\Exception\DsnFormatException;
use ManaPHP\Exception\NotImplementedException;
use ManaPHP\Exception\PreconditionException;
use PDO;

class Mssql extends Connection
{
    /**
     * SqlSrv constructor.
     *
     * @param string $url
     */
    public function __construct($url)
    {
        $this->_url = $url;

        $parts = parse_url($url);

        if ($parts['scheme'] !== 'mssql') {
            throw new DsnFormatException(['`%s` is invalid, `%s` scheme is not recognized', $url, $parts['scheme']]);
        }

        $this->_username = $parts['user'] ?? null;
        $this->_password = $parts['pass'] ?? null;

        $dsn = [];
        $use_dblib = DIRECTORY_SEPARATOR === '/';

        $host = $parts['host'] ?? '127.0.0.1';
        $port = $parts['port'] ?? '1433';

        $dsn[$use_dblib ? 'host' : 'Server'] = $host . ($use_dblib ? ':' : ',') . $port;

        if (isset($parts['path'])) {
            $path = trim($parts['path'], '/');
            if ($path !== '') {
                $dsn[$use_dblib ? 'dbname' : 'Database'] = $path;
            }
        }

        $this->_options[PDO::ATTR_STRINGIFY_FETCHES] = true;

        $dsn_parts = [];
        foreach ($dsn as $k => $v) {
            $dsn_parts[] = $k . '=' . $v;
        }

        $this->_dsn = ($use_dblib ? 'dblib:' : 'sqlsrv:') . implode(';', $dsn_parts);

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
        $parts = explode('.', $source);

        if (count($parts) === 1) {
            $fields = $this->query("exec sp_pkeys '$parts[0]'");
        } else {
            $fields = $this->query("exec sp_pkeys @table_name ='$parts[1]', @table_owner ='$parts[0]'");
        }

        $primaryKeys = count($fields) === 1 ? [$fields[0]['COLUMN_NAME']] : [];

        if (count($parts) === 1) {
            $fields = $this->query("exec sp_columns '$parts[0]'");
        } else {
            $fields = $this->query("exec sp_columns @table_name ='$parts[1]', @table_owner ='$parts[0]'");
        }

        $attributes = [];
        $autoIncrementAttribute = null;

        foreach ($fields as $field) {
            $fieldName = $field['COLUMN_NAME'];

            $attributes[] = $fieldName;

            if (!in_array($fieldName, $primaryKeys, true)) {
                $nonPrimaryKeys[] = $fieldName;
            }

            if ($field['TYPE_NAME'] === 'int identity') {
                $autoIncrementAttribute = $fieldName;
            }
        }

        return [
            Db::METADATA_ATTRIBUTES         => $attributes,
            Db::METADATA_PRIMARY_KEY        => $primaryKeys,
            Db::METADATA_AUTO_INCREMENT_KEY => $autoIncrementAttribute
        ];
    }

    /**
     * @return int
     * @throws \ManaPHP\Db\Exception
     */
    public function lastInsertId()
    {
        $row = $this->query('SELECT @@IDENTITY AS lid');
        return $row[0]['lid'];
    }

    /**
     * @param string $source
     *
     * @return $this
     * @throws \ManaPHP\Db\Exception
     */
    public function truncate($source)
    {
        $this->execute('TRUNCATE TABLE ' . $this->_escapeIdentifier($source));

        return $this;
    }

    /**
     * @param string $source
     *
     * @return void|static
     */
    public function drop($source)
    {
        throw new NotImplementedException(__METHOD__);
    }

    /**
     * @param null $schema
     *
     * @return array|void
     */
    public function getTables($schema = null)
    {
        throw new NotImplementedException(__METHOD__);
    }

    /**
     * @param string $table
     * @param null   $schema
     *
     * @return bool|void
     */
    public function tableExists($table, $schema = null)
    {
        throw new NotImplementedException(__METHOD__);
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function buildSql($params)
    {
        $sql = '';

        if (isset($params['fields'])) {
            $sql .= 'SELECT ';
            if (isset($params['limit']) && !isset($params['offset'])) {
                $sql .= 'TOP ' . $params['limit'] . ' ';
            }
            if (isset($params['distinct'])) {
                $sql .= 'DISTINCT ';
            }

            $sql .= $params['fields'];
            if (isset($params['limit'], $params['offset'])) {
                if (!isset($params['order'])) {
                    throw new PreconditionException('if use offset CLAUSE, must provide order CLAUSE.');
                }

                $sql .= ', ROW_NUMBER() OVER (ORDER BY ' . ($params['order'] ?? 'rand()') . ') AS _row_number_';
            }
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

        if (isset($params['limit'], $params['offset'])) {
            $offset = $params['offset'];
            $limit = $params['limit'];
            $sql = 'SELECT' . " t.* FROM ($sql) as t WHERE t._row_number_ BETWEEN $offset  + 1 AND $offset + $limit";
        }

        if (isset($params['group'])) {
            $sql .= ' GROUP BY ' . $params['group'];
        }

        if (isset($params['having'])) {
            $sql .= ' HAVING ' . $params['having'];
        }

        if (isset($params['order']) && !isset($params['offset'])) {
            $sql .= ' ORDER BY ' . $params['order'];
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
        return $sql;
    }
}