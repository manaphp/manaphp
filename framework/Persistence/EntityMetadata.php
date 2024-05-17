<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

use ManaPHP\Db\Model\InferenceInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\Str;
use ManaPHP\Persistence\Attribute\ColumnMap;
use ManaPHP\Persistence\Attribute\Connection;
use ManaPHP\Persistence\Attribute\DateFormat;
use ManaPHP\Persistence\Attribute\Fillable;
use ManaPHP\Persistence\Attribute\Guarded;
use ManaPHP\Persistence\Attribute\PrimaryKey;
use ManaPHP\Persistence\Attribute\ReferencedKey;
use ManaPHP\Persistence\Attribute\Repository;
use ManaPHP\Persistence\Attribute\Table;
use ManaPHP\Persistence\Attribute\Transiently;
use ManaPHP\Validating\ConstraintInterface;
use Psr\Container\ContainerInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;
use function in_array;
use function preg_match;
use function sprintf;

class EntityMetadata implements EntityMetadataInterface
{
    #[Autowired] protected ContainerInterface $container;
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

    protected array $repository = [];

    protected function getClassReflection(string $entityClass): ReflectionClass
    {
        if (($rClass = $this->rClass[$entityClass] ?? null) === null) {
            $rClass = new ReflectionClass($entityClass);
            $this->rClass[$entityClass] = $rClass;
        }

        return $rClass;
    }

    protected function getClassAttribute(string $entityClass, string $name): ?object
    {
        $rClass = $this->getClassReflection($entityClass);
        $attributes = $rClass->getAttributes($name, ReflectionAttribute::IS_INSTANCEOF);
        if (($attribute = $attributes[0] ?? null) !== null) {
            return $attribute->newInstance();
        } else {
            return null;
        }
    }

    protected function getTableInternal(string $entityClass): string
    {
        if (($attribute = $this->getClassAttribute($entityClass, Table::class)) !== null) {
            /** @var  Table $attribute */
            return $attribute->name;
        } else {
            return Str::snakelize(($pos = strrpos($entityClass, '\\')) === false ? $entityClass : substr($entityClass, $pos + 1));
        }
    }

    public function getTable(string $entityClass): string
    {
        if (($table = $this->table[$entityClass] ?? null) === null) {
            $table = $this->table[$entityClass] = $this->getTableInternal($entityClass);
        }

        return $table;
    }

    protected function getConnectionInternal(string $entityClass): string
    {
        if (($attribute = $this->getClassAttribute($entityClass, Connection::class)) !== null) {
            /** @var Connection $attribute */
            return $attribute->name;
        } else {
            return 'default';
        }
    }

    public function getConnection(string $entityClass): string
    {
        if (($connection = $this->connection[$entityClass] ?? null) === null) {
            $connection = $this->connection[$entityClass] = $this->getConnectionInternal($entityClass);
        }

        return $connection;
    }

    protected function getPrimaryKeyInternal(string $entityClass): string
    {
        if (($attribute = $this->getClassAttribute($entityClass, PrimaryKey::class)) !== null) {
            /** @var PrimaryKey $attribute */
            return $attribute->name;
        } else {
            return $this->inference->primaryKey($entityClass);
        }
    }

    public function getPrimaryKey(string $entityClass): string
    {
        if (($primaryKey = $this->primaryKey[$entityClass] ?? null) === null) {
            $primaryKey = $this->primaryKey[$entityClass] = $this->getPrimaryKeyInternal($entityClass);
        }

        return $primaryKey;
    }

    protected function getReferencedKeyInternal(string $entityClass): string
    {
        if (($attribute = $this->getClassAttribute($entityClass, ReferencedKey::class)) !== null) {
            /** @var ReferencedKey $attribute */
            return $attribute->name;
        } else {
            $primaryKey = $this->getPrimaryKey($entityClass);
            if ($primaryKey !== 'id') {
                return $primaryKey;
            } else {
                $table = $this->getTable($entityClass);

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

    public function getReferencedKey(string $entityClass): string
    {
        if (($referencedKey = $this->referencedKey[$entityClass] ?? null) === null) {
            $referencedKey = $this->referencedKey[$referencedKey] = $this->getReferencedKeyInternal($entityClass);
        }

        return $referencedKey;
    }

    public function getFields(string $entityClass): array
    {
        if (($fields = $this->fields[$entityClass] ?? null) === null) {
            $fields = [];
            foreach ((new ReflectionClass($entityClass))->getProperties() as $property) {
                if ($property->isReadOnly() || $property->isStatic()) {
                    continue;
                }

                if ($property->getAttributes(Transiently::class, ReflectionAttribute::IS_INSTANCEOF)) {
                    continue;
                }
                $fields[] = $property->getName();
            }

            $this->fields[$entityClass] = $fields;
        }

        return $fields;
    }

    protected function getColumnMapInternal(string $entityClass): array
    {
        $columnMap = [];
        if (($attribute = $this->getClassAttribute($entityClass, ColumnMap::class)) !== null) {
            /** @var ColumnMap $attribute */
            $columnMap = $attribute->get($this->getFields($entityClass));
        }

        return $columnMap;
    }

    public function getColumnMap(string $entityClass): array
    {
        if (($columnMap = $this->columnMap[$entityClass] ?? null) === null) {
            $columnMap = $this->columnMap[$entityClass] = $this->getColumnMapInternal($entityClass);
        }

        return $columnMap;
    }

    protected function getFillableInternal(string $entityClass): array
    {
        if (($attribute = $this->getClassAttribute($entityClass, Fillable::class)) !== null) {
            /** @var Fillable $attribute */
            $fillable = $attribute->fields;
        } elseif (($attribute = $this->getClassAttribute($entityClass, Guarded::class)) !== null) {
            /** @var Guarded $attribute */
            $guarded = $attribute->fields;
            $fillable = [];
            foreach ($this->getFields($entityClass) as $field) {
                if (!in_array($field, $guarded, true)) {
                    $fillable[] = $field;
                }
            }
        } else {
            $fillable = [];
            $rClass = new ReflectionClass($entityClass);
            foreach ($rClass->getProperties(ReflectionProperty::IS_PUBLIC) as $rProperty) {
                if ($rProperty->getAttributes(ConstraintInterface::class, ReflectionAttribute::IS_INSTANCEOF) !== []) {
                    $fillable[] = $rProperty->getName();
                }
            }
        }

        $primaryKey = $this->getPrimaryKey($entityClass);
        if (!in_array($primaryKey, $fillable, true)) {
            $fillable[] = $primaryKey;
        }

        return $fillable;
    }

    public function getFillable(string $entityClass): array
    {
        if (($fillable = $this->fillable[$entityClass] ?? null) === null) {
            $fillable = $this->fillable[$entityClass] = $this->getFillableInternal($entityClass);
        }

        return $fillable;
    }

    protected function getDateFormatInternal(string $entityClass): string
    {
        if (($attribute = $this->getClassAttribute($entityClass, DateFormat::class)) !== null) {
            /**@var DateFormat $attribute */
            return $attribute->get();
        } else {
            return 'U';
        }
    }

    public function getDateFormat(string $entityClass): string
    {
        if (($dateFormat = $this->dateFormat[$entityClass] ?? null) === null) {
            $dateFormat = $this->dateFormat[$entityClass] = $this->getDateFormatInternal($entityClass);
        }

        return $dateFormat;
    }

    public function getRepository(string $entityClass): RepositoryInterface
    {
        if (($repository = $this->repository[$entityClass] ?? null) === null) {
            $rClass = new ReflectionClass($entityClass);
            if (($attributes = $rClass->getAttributes(Repository::class)) !== []) {
                /** @var Repository $rRepository */
                $rRepository = $attributes[0]->newInstance();
                $repository = $rRepository->name;
            } else {
                if (preg_match('#^(.*)\\\\Entities\\\\(\\w+)$#', $entityClass, $match) === 1) {
                    $repository = $match[1] . '\\Repositories\\' . $match[2] . 'Repository';
                } elseif (preg_match('#^(.*)\\\\Entity\\\\(\\w+)$#', $entityClass, $match) === 1) {
                    $repository = $match[1] . '\\Repository\\' . $match[2] . 'Repository';
                } else {
                    throw new MisuseException(sprintf('repository of `%s` not found', $entityClass));
                }
            }
            $this->repository[$entityClass] = $repository;
        }

        return $this->container->get($repository);
    }
}