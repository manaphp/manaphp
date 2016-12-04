<?php
namespace ManaPHP\Db\Adapter;

use ManaPHP\Db;
use ManaPHP\Mvc\Model\Metadata;
use ManaPHP\Db\Adapter\SqlSrv\Exception as SqlSrvException;

class SqlSrv extends Db
{
    /**
     * SqlSrv constructor.
     *
     * @param array $options
     */
    public function __construct($options)
    {
        if (is_object($options)) {
            $options = (array)$options;
        }

        if (isset($options['options'])) {
            $this->_options = $options['options'];
        }

        $this->_options[\PDO::ATTR_STRINGIFY_FETCHES] = true;

        $this->_username = isset($options['username']) ? $options['username'] : null;
        $this->_password = isset($options['password']) ? $options['password'] : null;

        if (isset($options['dsn'])) {
            $this->_dsn = $options['dsn'];
        } else {
            unset($options['username'], $options['password'], $options['options']);
            if (DIRECTORY_SEPARATOR === '/') {
                if (isset($options['server']) && !isset($options['host'])) {
                    $options['host'] = $options['server'];
                    unset($options['server']);
                }

                if (isset($options['database']) && !isset($options['dbname'])) {
                    $options['dbname'] = $options['database'];
                    unset($options['database']);
                }
            } else {
                if (isset($options['host']) && !isset($options['server'])) {
                    $options['server'] = $options['host'];
                    unset($options['host']);
                }

                if (isset($options['dbname']) && !isset($options['database'])) {
                    $options['database'] = $options['dbname'];
                    unset($options['dbname']);
                }
            }

            $dsn_parts = [];
            foreach ($options as $k => $v) {
                $dsn_parts[] = $k . '=' . $v;
            }

            $this->_dsn = (DIRECTORY_SEPARATOR === '/' ? 'dblib:' : 'sqlsrv:') . implode(';', $dsn_parts);
        }

        parent::__construct();
    }

    public function getMetadata($source)
    {
        $parts = explode('.', $source);
        if (count($parts) === 1) {
            $columns = $this->fetchAll("exec sp_columns '$parts[0]'");
        } else {
            $columns = $this->fetchAll("exec sp_columns @table_name='$parts[1]', @table_owner='$parts[0]'");
        }

        $attributes = [];
        $primaryKeys = [];
        $nonPrimaryKeys = [];
        $autoIncrementAttribute = null;

        foreach ($columns as $column) {
            $columnName = $column['COLUMN_NAME'];

            $attributes[] = $columnName;

            if ($column['TYPE_NAME'] === 'int identity') {
                $primaryKeys[] = $columnName;
            } else {
                $nonPrimaryKeys[] = $columnName;
            }

            if ($column['TYPE_NAME'] === 'int identity') {
                $autoIncrementAttribute = $columnName;
            }
        }

        $r = [
            Metadata::MODEL_ATTRIBUTES => $attributes,
            Metadata::MODEL_PRIMARY_KEY => $primaryKeys,
            Metadata::MODEL_NON_PRIMARY_KEY => $nonPrimaryKeys,
            Metadata::MODEL_IDENTITY_COLUMN => $autoIncrementAttribute
        ];

        return $r;
    }

    /**
     * @return int
     */
    public function lastInsertId()
    {
        $row = $this->fetchOne('SELECT @@IDENTITY AS lid');
        return $row['lid'];
    }

    public function truncateTable($source)
    {
        // TODO: Implement truncateTable() method.
    }

    public function buildSql($params)
    {
        $sql = '';

        if (isset($params['columns'])) {

            $sql .= 'SELECT ';
            if (isset($params['limit']) && !isset($params['offset'])) {
                $sql .= ' TOP ' . $params['limit'] . ' ';
            }
            if (isset($params['distinct'])) {
                $sql .= 'DISTINCT ';
            }

            $sql .= $params['columns'];
            if (isset($params['limit']) && isset($params['offset'])) {
                if(!isset($params['order'])){
                    throw new SqlSrvException('if use offset CLAUSE, must provide order CLAUSE.');
                }

                $sql .= ', ROW_NUMBER() OVER (ORDER BY ' . (isset($params['order']) ? $params['order'] : 'rand()') . ') AS row_number';
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

        if (isset($params['limit']) && isset($params['offset'])) {
            $sql = 'SELECT' . ' t.* FROM (' . $sql . ') as t WHERE t.row_number BETWEEN ' . $params['limit'] . ' + 1 AND ' . $params['limit'] . ' + ' . $params['offset'];
        }

        if (isset($params['group'])) {
            $sql .= ' GROUP BY ' . $params['group'];
        }

        if (isset($params['having'])) {
            $sql .= ' HAVING ' . $params['having'];
        }

        if (isset($params['order']) && !isset($params['offset'])) {
            $sql .= ' ORDER BY' . $params['order'];
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