<?php
declare(strict_types=1);

namespace ManaPHP\Data\Model;

use ManaPHP\Component;
use ManaPHP\Data\Model\Attribute\AutoIncrementField;
use ManaPHP\Data\Model\Attribute\ColumnMap;
use ManaPHP\Data\Model\Attribute\Connection;
use ManaPHP\Data\Model\Attribute\Fillable;
use ManaPHP\Data\Model\Attribute\ForeignedKey;
use ManaPHP\Data\Model\Attribute\Guarded;
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
    protected array $columnMap = [];
    protected array $fillableFields = [];

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
                /** @var Connection $attribute */
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
                /** @var PrimaryKey $attribute */
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
                /** @var ForeignedKey $attribute */
                $foreignedKey = $attribute->get();
            } else {
                $primaryKey = $this->getPrimaryKey($model);
                if ($primaryKey !== 'id') {
                    $foreignedKey = $primaryKey;
                } else {
                    $table = $this->getTable($model);

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

    public function getAutoIncrementField(string $model): ?string
    {
        if (($autoIncrementField = $this->autoIncrementFields[$model] ?? null) === null
            && !array_key_exists($model, $this->autoIncrementFields)
        ) {
            if (($attribute = $this->getClassAttribute($model, AutoIncrementField::class)) !== null) {
                /** @var AutoIncrementField $attribute */
                $autoIncrementField = $attribute->get();
            } else {
                $autoIncrementField = $this->getPrimaryKey($model);
            }
            $this->autoIncrementFields[$model] = $autoIncrementField;
        }

        return $autoIncrementField;
    }

    public function getColumnMap(string $model): array
    {
        if (($columnMap = $this->columnMap[$model] ?? null) === null) {
            $columnMap = [];
            if (($attribute = $this->getClassAttribute($model, ColumnMap::class)) !== null) {
                /** @var ColumnMap $attribute */
                $columnMap = $attribute->get($this->getFields($model));
            }

            $this->columnMap[$model] = $columnMap;
        }

        return $columnMap;
    }

    public function getFillableFields(string $model): array
    {
        if (($fillableFields = $this->fillableFields[$model] ?? null) === null) {
            if (($attribute = $this->getClassAttribute($model, Fillable::class)) !== null) {
                /** @var Fillable $attribute */
                $fillableFields = $attribute->get();
            } elseif (($attribute = $this->getClassAttribute($model, Guarded::class)) !== null) {
                /** @var Guarded $attribute */
                $guarded = $attribute->get();
                foreach ($this->getFields($model) as $field) {
                    if (!in_array($field, $guarded, true)) {
                        $fillableFields[] = $field;
                    }
                }
            } else {
                $fillableFields[] = array_keys($this->those->get($model)->rules());
            }
            $this->fillableFields[$model] = $fillableFields;
        }

        return $fillableFields;
    }
}