<?php
declare(strict_types=1);

namespace ManaPHP\Data\Model;

use ManaPHP\Component;

/**
 * @property-read \ManaPHP\Data\Model\ThoseInterface       $those
 * @property-read \ManaPHP\Data\Db\Model\InferrerInterface $inferrer
 */
class Manager extends Component implements ManagerInterface
{
    protected array $tables = [];
    protected array $connections = [];
    protected array $primaryKeys = [];
    protected array $foreignedKeys = [];
    protected array $fields = [];
    protected array $jsonFields = [];

    public function getTable(string $model): string
    {
        if (($table = $this->tables[$model] ?? null) === null) {
            $table = $this->those->get($model)->table();
            $this->tables[$model] = $table;
        }

        return $table;
    }

    public function getConnection(string $model): string
    {
        if (($connection = $this->connections[$model] ?? null) === null) {
            $connection = $this->those->get($model)->connection();
            $this->connections[$model] = $connection;
        }

        return $connection;
    }

    public function getPrimaryKey(string $model): string
    {
        if (($primaryKey = $this->primaryKeys[$model] ?? null) === null) {
            $primaryKey = $this->those->get($model)->primaryKey();
            $this->primaryKeys[$model] = $primaryKey;
        }

        return $primaryKey;
    }

    public function getForeignedKey(string $model): string
    {
        if (($foreignedKey = $this->foreignedKeys[$model] ?? null) === null) {
            $foreignedKey = $this->those->get($model)->foreignedKey();
            $this->foreignedKeys[$foreignedKey] = $foreignedKey;
        }

        return $foreignedKey;
    }

    public function getFields(string $model): array
    {
        if (($fields = $this->fields[$model] ?? null) === null) {
            $fields = $this->inferrer->fields($model);
            $this->fields[$model] = $fields;
        }

        return $fields;
    }

    public function getJsonFields(string $model): array
    {
        if (($jsonFields = $this->jsonFields[$model] ?? null) === null) {
            $jsonFields = $this->those->get($model)->jsonFields();
            $this->jsonFields[$model] = $jsonFields;
        }

        return $jsonFields;
    }
}