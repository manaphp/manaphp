<?php
namespace ManaPHP\Db\Adapter;

use ManaPHP\Db;
use ManaPHP\Db\Adapter\Mssql\Exception as MssqlException;

class Mssql extends Db
{
    /**
     * SqlSrv constructor.
     *
     * @param array|string $options
     *
     * @throws \ManaPHP\Db\Exception
     * @throws \ManaPHP\Db\Adapter\Mssql\Exception
     */
    public function __construct($options)
    {
        if (is_string($options)) {
            $url = $options;

            $parts = parse_url($options);

            $options = [];

            if ($parts['scheme'] !== 'mssql') {
                throw new MssqlException('`:url` is invalid, `:scheme` scheme is not recognized', ['url' => $url, 'scheme' => $parts['scheme']]);
            }

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
                $options['database'] = trim($parts['path'], '/');
            }
        } elseif (is_object($options)) {
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

                if (isset($options['server'], $options['port'])) {
                    $options['server'] .= ',' . $options['port'];
                    unset($options['port']);
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
            $fields = $this->fetchAll("exec sp_pkeys '$parts[0]'");
        } else {
            $fields = $this->fetchAll("exec sp_pkeys @table_name ='$parts[1]', @table_owner ='$parts[0]'");
        }

        $primaryKeys = count($fields) === 1 ? [$fields[0]['COLUMN_NAME']] : [];

        if (count($parts) === 1) {
            $fields = $this->fetchAll("exec sp_columns '$parts[0]'");
        } else {
            $fields = $this->fetchAll("exec sp_columns @table_name ='$parts[1]', @table_owner ='$parts[0]'");
        }

        $attributes = [];
        $nonPrimaryKeys = [];
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

        $r = [
            self::METADATA_ATTRIBUTES => $attributes,
            self::METADATA_PRIMARY_KEY => $primaryKeys,
            self::METADATA_NON_PRIMARY_KEY => $nonPrimaryKeys,
            self::METADATA_IDENTITY_FIELD => $autoIncrementAttribute
        ];

        return $r;
    }

    /**
     * @return int
     * @throws \ManaPHP\Db\Exception
     */
    public function lastInsertId()
    {
        $row = $this->fetchOne('SELECT @@IDENTITY AS lid');
        return $row['lid'];
    }

    /**
     * @param string $source
     *
     * @return $this
     * @throws \ManaPHP\Db\Exception
     */
    public function truncateTable($source)
    {
        $this->execute('TRUNCATE TABLE ' . $this->_escapeIdentifier($source));

        return $this;
    }

    public function dropTable($source)
    {
        throw new MssqlException('not implement');
    }

    public function getTables($schema = null)
    {
        throw new MssqlException('not implement');
    }

    public function tableExists($table, $schema = null)
    {
        throw new MssqlException('not implement');
    }

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
                    throw new MssqlException('if use offset CLAUSE, must provide order CLAUSE.');
                }

                $sql .= ', ROW_NUMBER() OVER (ORDER BY ' . (isset($params['order']) ? $params['order'] : 'rand()') . ') AS _row_number_';
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
            $sql = 'SELECT' . ' t.* FROM (' . $sql . ') as t WHERE t._row_number_ BETWEEN ' . $params['offset'] . ' + 1 AND ' . $params['offset'] . ' + ' . $params['limit'];
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