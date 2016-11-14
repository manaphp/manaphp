<?php
namespace ManaPHP\Db\Adapter;

use ManaPHP\Db;
use ManaPHP\Db\Adapter\Sqlite\Exception as SqliteException;
use ManaPHP\Mvc\Model\Metadata;

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
     * @param string|array $options
     *
     * @throws \ManaPHP\Db\Adapter\Sqlite\Exception
     */
    public function __construct($options)
    {
        $this->_type = 'sqlite';

        if (is_object($options)) {
            $options = (array)$options;
        } elseif (is_string($options)) {
            $options = ['file' => $options];
        }

        if (!isset($options['file'])) {
            throw new SqliteException('file is not provide to sqlite adapter.'/**m0c03cc731dd915d96*/);
        }
        $options['dsn'] = $options['file'];
        unset($options['file']);

        parent::__construct($options);
    }

    /**
     * @param string $source
     *
     * @return array
     * @throws \ManaPHP\Db\Exception
     */
    public function getMetadata($source)
    {
        $columns = $this->fetchAll('PRAGMA table_info(' . $this->escapeIdentifier($source) . ')', null, \PDO::FETCH_ASSOC);

        $attributes = [];
        $primaryKeys = [];
        $nonPrimaryKeys = [];
        $autoIncrementAttribute = null;

        foreach ($columns as $column) {
            $columnName = $column['name'];

            $attributes[] = $columnName;

            if ($column['pk'] === '1') {
                $primaryKeys[] = $columnName;
            } else {
                $nonPrimaryKeys = $columnName;
            }

            if ($column['pk'] === '1' && $column['type'] === 'INTEGER') {
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
     * Escapes a column/table/schema name
     * <code>
     * echo $connection->escapeIdentifier('my_table'); // `my_table`
     * echo $connection->escapeIdentifier('companies.name'); // `companies`.`name`
     * <code>
     *
     * @param string $identifier
     *
     * @return string
     */
    public function escapeIdentifier($identifier)
    {
        return "'" . $identifier . "'";
    }

    /**
     * @param string $source
     *
     * @return static
     * @throws \ManaPHP\Db\Exception
     */
    public function truncateTable($source)
    {
        $escapedTable = $this->escapeIdentifier($source);
        $this->execute('DELETE ' . 'FROM ' . $escapedTable);
        $this->execute('DELETE' . ' FROM sqlite_sequence WHERE name=' . $escapedTable);

        return $this;
    }
}