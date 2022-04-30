<?php
declare(strict_types=1);

namespace ManaPHP\Data\Model;

use ManaPHP\Component;
use ManaPHP\Data\Model\Attribute\Connection;
use ManaPHP\Data\Model\Attribute\ForeignedKey;
use ManaPHP\Data\Model\Attribute\JsonFields;
use ManaPHP\Data\Model\Attribute\PrimaryKey;
use ManaPHP\Data\Model\Attribute\Table;
use ManaPHP\Helper\Str;
use ReflectionClass;
use ReflectionAttribute;

/**
 * @property-read \ManaPHP\Data\Model\ThoseInterface       $those
 * @property-read \ManaPHP\Data\Db\Model\InferrerInterface $inferrer
 */
class Manager extends Component implements ManagerInterface
{
    protected array $rClasses = [];
    protected array $tables = [];
    protected array $connections = [];
    protected array $primaryKeys = [];
    protected array $foreignedKeys = [];
    protected array $fields = [];
    protected array $jsonFields = [];
    protected array $autoIncrementFields = [];

    protected function getClassReflection(string $model): ReflectionClass
    {
        if (($rClass = $this->rClasses[$model] ?? null) === null) {
            $rClass = new ReflectionClass($model);
            $this->rClasses[$model] = $rClass;
        }

        return $rClass;
    }

    protected function getClassAttribute(string $model, string $name): ?object
    {
        $rClass = $this->getClassReflection($model);
        $attributes = $rClass->getAttributes($name, ReflectionAttribute::IS_INSTANCEOF);
        if (($attribute = $attributes[0] ?? null) !== null) {
            return $attribute->newInstance();
        } else {
            return null;
        }
    }

    public function getTable(string $model): string
    {
        if (($table = $this->tables[$model] ?? null) === null) {
            if (($attribute = $this->getClassAttribute($model, Table::class)) !== null) {
                /** @var  Table $attribute */
                $table = $attribute->get();
            } else {
                $table = Str::snakelize(($pos = strrpos($model, '\\')) === false ? $model : substr($model, $pos + 1));
            }
            $this->tables[$model] = $table;
        }

        return $table;
    }

    public function getConnection(string $model): string
    {
        if (($connection = $this->connections[$model] ?? null) === null) {
            if (($attribute = $this->getClassAttribute($model, Connection::class)) !== null) {
                /** @var Connection $connection */
                $connection = $attribute->get();
            } else {
                $connection = 'default';
            }
            $this->connections[$model] = $connection;
        }

        return $connection;
    }

    public function getPrimaryKey(string $model): string
    {
        if (($primaryKey = $this->primaryKeys[$model] ?? null) === null) {
            if (($attribute = $this->getClassAttribute($model, PrimaryKey::class)) !== null) {
                /** @var PrimaryKey $primaryKey */
                $primaryKey = $attribute->get();
            } else {
                $primaryKey = $this->inferrer->primaryKey($model);
            }
            $this->primaryKeys[$model] = $primaryKey;
        }

        return $primaryKey;
    }

    public function getForeignedKey(string $model): string
    {
        if (($foreignedKey = $this->foreignedKeys[$model] ?? null) === null) {
            if (($attribute = $this->getClassAttribute($model, ForeignedKey::class)) !== null) {
                /** @var ForeignedKey $foreignedKey */
                $foreignedKey = $attribute->get();
            } else {
                $primaryKey = $this->getPrimaryKey(static::class);
                if ($primaryKey !== 'id') {
                    $foreignedKey = $primaryKey;
                } else {
                    $table = $this->getTable(static::class);

                    if (($pos = strpos($table, '.')) !== false) {
                        $table = substr($table, $pos + 1);
                    }

                    if (($pos = strpos($table, ':')) !== false) {
                        $foreignedKey = substr($table, 0, $pos) . '_id';
                    } else {
                        $foreignedKey = $table . '_id';
                    }
                }
            }
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
            if (($attribute = $this->getClassAttribute($model, JsonFields::class)) !== null) {
                /** @var JsonFields $attribute */
                $jsonFields = $attribute->get();
            } else {
                $jsonFields = [];
            }
            $this->jsonFields[$model] = $jsonFields;
        }

        return $jsonFields;
    }

    public function getAutoIncrementField(string $model): string
    {
        if (($autoIncrementField = $this->autoIncrementFields[$model] ?? null) === null) {
            $autoIncrementField = $this->those->get($model)->autoIncrementField();
            $this->autoIncrementFields[$model] = $autoIncrementField;
        }

        return $autoIncrementField;
    }
}