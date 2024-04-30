<?php
declare(strict_types=1);

namespace ManaPHP\Db\Connection\Adapter;

use JetBrains\PhpStorm\ArrayShape;
use ManaPHP\Db\AbstractConnection;
use ManaPHP\Db\Db;
use ManaPHP\Db\SqlFragmentable;
use ManaPHP\Exception\DsnFormatException;
use ManaPHP\Exception\InvalidArgumentException;
use PDO;
use function count;
use function is_int;
use function is_string;

class Mysql extends AbstractConnection
{
    protected string $charset = 'UTF8';

    /** @noinspection PhpTypedPropertyMightBeUninitializedInspection */
    public function __construct()
    {
        $parts = parse_url($this->uri);

        if ($parts['scheme'] !== 'mysql') {
            throw new DsnFormatException(
                ['`{1}` is invalid, `{2}` scheme is not recognized', $this->uri, $parts['scheme']]
            );
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

        $this->options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES '$this->charset'";

        $dsn_parts = [];
        foreach ($dsn as $k => $v) {
            $dsn_parts[] = $k . '=' . $v;
        }
        $this->dsn = 'mysql:' . implode(';', $dsn_parts);

        parent::__construct();
    }

    #[ArrayShape([Db::METADATA_ATTRIBUTES          => 'array',
                  Db::METADATA_PRIMARY_KEY         => 'array',
                  Db::METADATA_AUTO_INCREMENT_KEY  => 'mixed|null',
                  Db::METADATA_INT_TYPE_ATTRIBUTES => 'array'])]
    public function getMetadata(string $table): array
    {
        $fields = $this->query('DESCRIBE ' . $this->escapeIdentifier($table), [], PDO::FETCH_NUM);

        $attributes = [];
        $primaryKeys = [];
        $autoIncrementAttribute = null;
        $intTypes = [];

        foreach ($fields as $field) {
            $fieldName = $field[0];
            $type = $field[1];

            $attributes[$fieldName] = $type;

            if ($field[3] === 'PRI') {
                $primaryKeys[] = $fieldName;
            }

            if ($field[5] === 'auto_increment') {
                $autoIncrementAttribute = $fieldName;
            }

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

    public function truncate(string $table): void
    {
        $this->execute('TRUNCATE' . ' TABLE ' . $this->escapeIdentifier($table));
    }

    public function drop(string $table): void
    {
        $this->execute('DROP' . ' TABLE IF EXISTS ' . $this->escapeIdentifier($table));
    }

    public function getTables(?string $schema = null): array
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

    public function tableExists(string $table): bool
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

    public function buildSql(array $params): string
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

    public function replaceQuoteCharacters(string $sql): string
    {
        return str_contains($sql, '[') ? preg_replace(/**@lang text */ '#\[([a-z_]\w*)\]#i', '`\\1`', $sql) : $sql;
    }

    public function bulkInsert(string $table, array $records): int
    {
        if (!$records) {
            throw new InvalidArgumentException(['Unable to insert into {table} table without data', 'table' => $table]);
        }

        $fields = array_keys($records[0]);
        $insertedFields = '[' . implode('],[', $fields) . ']';

        $pdo = $this->getPdo();

        $rows = [];
        foreach ($records as $record) {
            $row = [];
            foreach ($record as $value) {
                $row[] = is_string($value) ? $pdo->quote($value) : $value;
            }

            $rows[] = '(' . implode(',', $row) . ')';
        }

        $sql
            = /**@lang text */
            "INSERT INTO {$this->escapeIdentifier($table)} ($insertedFields) VALUES " . implode(', ', $rows);

        return $this->execute($sql);
    }

    public function upsert(string $table, array $insertFieldValues, array $updateFieldValues = [],
        ?string $primaryKey = null
    ): int {
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

        return $this->execute($sql, $bind);
    }
}