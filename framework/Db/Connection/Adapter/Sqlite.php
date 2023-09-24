<?php
declare(strict_types=1);

namespace ManaPHP\Db\Connection\Adapter;

use JetBrains\PhpStorm\ArrayShape;
use ManaPHP\AliasInterface;
use ManaPHP\Db\AbstractConnection;
use ManaPHP\Db\Db;
use ManaPHP\Di\Attribute\Inject;
use PDO;

class Sqlite extends AbstractConnection
{
    #[Inject] protected AliasInterface $alias;

    /** @noinspection PhpTypedPropertyMightBeUninitializedInspection */
    public function __construct()
    {
        $file = $this->uri;

        $this->dsn = 'sqlite:' . ($file[0] === '@' ? $this->alias->resolve($file) : $file);
        parent::__construct();
    }

    #[ArrayShape([Db::METADATA_ATTRIBUTES         => "array",
                  Db::METADATA_PRIMARY_KEY        => "array",
                  Db::METADATA_AUTO_INCREMENT_KEY => "mixed|null"])]
    public function getMetadata(string $table): array
    {
        $fields = $this->query('PRAGMA table_info(' . $this->escapeIdentifier($table) . ')');

        $attributes = [];
        $primaryKeys = [];
        $autoIncrementAttribute = null;

        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $type = $field['type'];

            $attributes[$fieldName] = $type;

            if ($field['pk'] === '1') {
                $primaryKeys[] = $fieldName;
            }

            if ($field['pk'] === '1' && $type === 'INTEGER') {
                $autoIncrementAttribute = $fieldName;
            }
        }

        return [
            Db::METADATA_ATTRIBUTES         => $attributes,
            Db::METADATA_PRIMARY_KEY        => $primaryKeys,
            Db::METADATA_AUTO_INCREMENT_KEY => $autoIncrementAttribute,
        ];
    }

    public function truncate(string $table): void
    {
        $this->execute('DELETE' . ' FROM ' . $this->escapeIdentifier($table));
        $this->execute('DELETE' . ' FROM sqlite_sequence WHERE name=:name', ['name' => $table]);
    }

    public function drop(string $table): void
    {
        $this->execute('DROP' . ' TABLE IF EXISTS ' . $this->escapeIdentifier($table));
    }

    public function getTables(?string $schema = null): array
    {
        $sql = 'SELECT' . " tbl_name FROM sqlite_master WHERE type = 'table' ORDER BY tbl_name";
        $tables = [];
        foreach ($this->query($sql) as $row) {
            $tables[] = $row['tbl_name'];
        }

        return $tables;
    }

    public function tableExists(string $table): bool
    {
        $parts = explode('.', str_replace('[]`', '', $table));

        $sql
            = /** @lang text */
            "SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM sqlite_master"
            . " WHERE type='table' AND tbl_name='$parts[0]'";

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

    /**
     * @param string $sql
     *
     * @return string
     */
    public function replaceQuoteCharacters(string $sql): string
    {
        return str_contains($sql, '[') ? preg_replace(/**@lang text */ '#\[([a-z_]\w*)\]#i', '`\\1`', $sql) : $sql;
    }
}