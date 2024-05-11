<?php
declare(strict_types=1);

namespace ManaPHP\Model;

use ManaPHP\Db\Model\InferenceInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\Str;
use ManaPHP\Model\Attribute\ColumnMap;
use ManaPHP\Model\Attribute\Connection;
use ManaPHP\Model\Attribute\DateFormat;
use ManaPHP\Model\Attribute\Fillable;
use ManaPHP\Model\Attribute\Guarded;
use ManaPHP\Model\Attribute\PrimaryKey;
use ManaPHP\Model\Attribute\ReferencedKey;
use ManaPHP\Model\Attribute\Table;
use ManaPHP\Validating\ConstraintInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;
use function in_array;

class Models implements ModelsInterface
{
    #[Autowired] protected ThoseInterface $those;
    #[Autowired] protected InferenceInterface $inference;

    protected array $rClass = [];
    protected array $table = [];
    protected array $connection = [];
    protected array $primaryKey = [];
    protected array $referencedKey = [];
    protected array $fields = [];
    protected array $columnMap = [];
    protected array $fillable = [];
    protected array $dateFormat = [];

    protected function getClassReflection(string $model): ReflectionClass
    {
        if (($rClass = $this->rClass[$model] ?? null) === null) {
            $rClass = new ReflectionClass($model);
            $this->rClass[$model] = $rClass;
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

    protected function getTableInternal(string $model): string
    {
        if (($attribute = $this->getClassAttribute($model, Table::class)) !== null) {
            /** @var  Table $attribute */
            return $attribute->name;
        } else {
            return Str::snakelize(($pos = strrpos($model, '\\')) === false ? $model : substr($model, $pos + 1));
        }
    }

    public function getTable(string $model): string
    {
        if (($table = $this->table[$model] ?? null) === null) {
            $table = $this->table[$model] = $this->getTableInternal($model);
        }

        return $table;
    }

    protected function getConnectionInternal(string $model): string
    {
        if (($attribute = $this->getClassAttribute($model, Connection::class)) !== null) {
            /** @var Connection $attribute */
            return $attribute->name;
        } else {
            return 'default';
        }
    }

    public function getConnection(string $model): string
    {
        if (($connection = $this->connection[$model] ?? null) === null) {
            $connection = $this->connection[$model] = $this->getConnectionInternal($model);
        }

        return $connection;
    }

    protected function getPrimaryKeyInternal(string $model): string
    {
        if (($attribute = $this->getClassAttribute($model, PrimaryKey::class)) !== null) {
            /** @var PrimaryKey $attribute */
            return $attribute->name;
        } else {
            return $this->inference->primaryKey($model);
        }
    }

    public function getPrimaryKey(string $model): string
    {
        if (($primaryKey = $this->primaryKey[$model] ?? null) === null) {
            $primaryKey = $this->primaryKey[$model] = $this->getPrimaryKeyInternal($model);
        }

        return $primaryKey;
    }

    protected function getReferencedKeyInternal(string $model): string
    {
        if (($attribute = $this->getClassAttribute($model, ReferencedKey::class)) !== null) {
            /** @var ReferencedKey $attribute */
            return $attribute->name;
        } else {
            $primaryKey = $this->getPrimaryKey($model);
            if ($primaryKey !== 'id') {
                return $primaryKey;
            } else {
                $table = $this->getTable($model);

                if (($pos = strpos($table, '.')) !== false) {
                    $table = substr($table, $pos + 1);
                }

                if (($pos = strpos($table, ':')) !== false) {
                    return substr($table, 0, $pos) . '_id';
                } else {
                    return $table . '_id';
                }
            }
        }
    }

    public function getReferencedKey(string $model): string
    {
        if (($referencedKey = $this->referencedKey[$model] ?? null) === null) {
            $referencedKey = $this->referencedKey[$referencedKey] = $this->getReferencedKeyInternal($model);
        }

        return $referencedKey;
    }

    protected function getFieldsInternal(string $model): array
    {
        return $this->inference->fields($model);
    }

    public function getFields(string $model): array
    {
        if (($fields = $this->fields[$model] ?? null) === null) {
            $fields = $this->fields[$model] = $this->getFieldsInternal($model);
        }

        return $fields;
    }

    protected function getColumnMapInternal(string $model): array
    {
        $columnMap = [];
        if (($attribute = $this->getClassAttribute($model, ColumnMap::class)) !== null) {
            /** @var ColumnMap $attribute */
            $columnMap = $attribute->get($this->getFields($model));
        }

        return $columnMap;
    }

    public function getColumnMap(string $model): array
    {
        if (($columnMap = $this->columnMap[$model] ?? null) === null) {
            $columnMap = $this->columnMap[$model] = $this->getColumnMapInternal($model);
        }

        return $columnMap;
    }

    protected function getFillableInternal(string $model): array
    {
        if (($attribute = $this->getClassAttribute($model, Fillable::class)) !== null) {
            /** @var Fillable $attribute */
            $fillable = $attribute->fields;
        } elseif (($attribute = $this->getClassAttribute($model, Guarded::class)) !== null) {
            /** @var Guarded $attribute */
            $guarded = $attribute->fields;
            $fillable = [];
            foreach ($this->getFields($model) as $field) {
                if (!in_array($field, $guarded, true)) {
                    $fillable[] = $field;
                }
            }
        } else {
            $fillable = [];
            $rClass = new ReflectionClass($model);
            foreach ($rClass->getProperties(ReflectionProperty::IS_PUBLIC) as $rProperty) {
                if ($rProperty->getAttributes(ConstraintInterface::class, ReflectionAttribute::IS_INSTANCEOF) !== []) {
                    $fillable[] = $rProperty->getName();
                }
            }
        }

        $primaryKey = $this->getPrimaryKey($model);
        if (!in_array($primaryKey, $fillable, true)) {
            $fillable[] = $primaryKey;
        }

        return $fillable;
    }

    public function getFillable(string $model): array
    {
        if (($fillable = $this->fillable[$model] ?? null) === null) {
            $fillable = $this->fillable[$model] = $this->getFillableInternal($model);
        }

        return $fillable;
    }

    protected function getDateFormatInternal(string $model): string
    {
        if (($attribute = $this->getClassAttribute($model, DateFormat::class)) !== null) {
            /**@var DateFormat $attribute */
            return $attribute->get();
        } else {
            return 'U';
        }
    }

    public function getDateFormat(string $model): string
    {
        if (($dateFormat = $this->dateFormat[$model] ?? null) === null) {
            $dateFormat = $this->dateFormat[$model] = $this->getDateFormatInternal($model);
        }

        return $dateFormat;
    }
}