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
 * @package ManaPHP\Db\Adapter
 */
class Mysql extends Db
{
    /**
     * \ManaPHP\Db\Adapter constructor
     *
     * @param array|\ConfManaPHP\Db\Adapter\Mysql $options
     */
    public function __construct($options)
    {
        $this->_type = 'mysql';

        if (is_object($options)) {
            $options = (array)$options;
        }

        /** @noinspection AdditionOperationOnArraysInspection */
        $defaultOptions = ['host' => 'localhost', 'port' => 3306, 'username' => 'root', 'password' => '', 'options' => []];
        $options = array_merge($defaultOptions, $options);

        if (!isset($options['options'][\PDO::MYSQL_ATTR_INIT_COMMAND])) {
            $options['options'][\PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES 'UTF8'";
        }

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
        $escapedTable = $this->escapeIdentifier($source);
        $columns = $this->fetchAll('DESCRIBE ' . $escapedTable, null, \PDO::FETCH_NUM);

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
        $list = [];
        foreach (explode('.', $identifier) as $id) {
            if ($identifier[0] === '`') {
                $list[] = $id;
            } else {
                $list[] = "`$id`";
            }
        }

        return implode('.', $list);
    }

    /**
     * @param string $source
     *
     * @return static
     * @throws \ManaPHP\Db\Exception
     */
    public function truncateTable($source)
    {
        $this->execute('TRUNCATE TABLE ' . $this->escapeIdentifier($source));
    }
}