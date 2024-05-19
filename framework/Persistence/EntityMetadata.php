<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Di\ContainerInterface;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Persistence\Attribute\Column;
use ManaPHP\Persistence\Attribute\Connection;
use ManaPHP\Persistence\Attribute\DateFormat;
use ManaPHP\Persistence\Attribute\Fillable;
use ManaPHP\Persistence\Attribute\Guarded;
use ManaPHP\Persistence\Attribute\Id;
use ManaPHP\Persistence\Attribute\NamingStrategy;
use ManaPHP\Persistence\Attribute\ReferencedKey;
use ManaPHP\Persistence\Attribute\RelationInterface;
use ManaPHP\Persistence\Attribute\Repository;
use ManaPHP\Persistence\Attribute\Table;
use ManaPHP\Persistence\Attribute\Transiently;
use ManaPHP\Validating\ConstraintInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;
use function in_array;
use function preg_match;
use function property_exists;
use function sprintf;

class EntityMetadata implements EntityMetadataInterface
{
    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected ThoseInterface $those;

    #[Config] protected string $defaultNamingStrategy = 'ManaPHP\Persistence\UnderscoreNamingStrategy';

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
    protected array $namingStrategy = [];
    protected array $constraints = [];
    protected array $relations = [];

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

    /**
     * @template T
     * @param ReflectionProperty $property
     * @param class-string<T>    $name
     *
     * @return T
     */
    protected function getPropertyAttribute(ReflectionProperty $property, string $name): ?object
    {
        if (($attribute = $property->getAttributes($name, ReflectionAttribute::IS_INSTANCEOF)[0] ?? null) !== null) {
            return $attribute->newInstance();
        } else {
            return null;
        }
    }

    public function getTable(string $entityClass): string
    {
        if (($table = $this->table[$entityClass] ?? null) === null) {
            if (($attribute = $this->getClassAttribute($entityClass, Table::class)) !== null) {
                $table = $attribute->name;
            } else {
                $table = $this->getNamingStrategy($entityClass)->classToTableName($entityClass);
            }
            $this->table[$entityClass] = $table;
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

    public function getPrimaryKey(string $entityClass): string
    {
        if (($primaryKey = $this->primaryKey[$entityClass] ?? null) === null) {
            foreach ($this->getClassReflection($entityClass)->getProperties() as $property) {
                if ($property->isReadOnly() || $property->isStatic()) {
                    continue;
                }

                if ($property->getAttributes(Id::class) !== []) {
                    return $this->primaryKey[$entityClass] = $property->getName();
                }
            }

            if (property_exists($entityClass, 'id')) {
                return $this->primaryKey[$entityClass] = 'id';
            }

            throw new MisuseException('Primary key not found for entity: ' . $entityClass);
        } else {
            return $primaryKey;
        }
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
            foreach ($this->getClassReflection($entityClass)->getProperties() as $property) {
                if ($property->isReadOnly() || $property->isStatic()) {
                    continue;
                }

                if ($property->getAttributes(Transiently::class, ReflectionAttribute::IS_INSTANCEOF) !== []) {
                    continue;
                }

                $fields[] = $property->getName();
            }

            $this->fields[$entityClass] = $fields;
        }

        return $fields;
    }

    public function getColumnMap(string $entityClass): array
    {
        $namingStrategy = $this->getNamingStrategy($entityClass);

        if (($columnMap = $this->columnMap[$entityClass] ?? null) === null) {
            $columnMap = [];
            foreach ($this->getClassReflection($entityClass)->getProperties() as $property) {
                if ($property->isReadOnly() || $property->isStatic()) {
                    continue;
                }

                if ($property->getAttributes(Transiently::class, ReflectionAttribute::IS_INSTANCEOF) !== []) {
                    continue;
                }

                $propertyName = $property->getName();

                if (($attribute = $this->getPropertyAttribute($property, Column::class)) !== null) {
                    $columnName = $attribute->name ?? $namingStrategy->propertyToColumnName($propertyName);
                } else {
                    $columnName = $namingStrategy->propertyToColumnName($propertyName);
                }

                if ($propertyName !== $columnName) {
                    $columnMap[$propertyName] = $columnName;
                }
            }
            $this->columnMap[$entityClass] = $columnMap;
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
            if (($attribute = $this->getClassAttribute($entityClass, Repository::class)) !== null) {
                $repository = $attribute->name;
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

    public function getNamingStrategy(string $entityClass): NamingStrategyInterface
    {
        if (($namingStrategy = $this->namingStrategy[$entityClass] ?? null) === null) {
            if (($attribute = $this->getClassAttribute($entityClass, NamingStrategy::class)) !== null) {
                $namingStrategy = $attribute->strategy;
            } else {
                $namingStrategy = $this->defaultNamingStrategy;
            }
            $this->namingStrategy[$entityClass] = $namingStrategy;
        }

        return $this->container->get($namingStrategy);
    }

    public function getConstraints(string $entityClass): array
    {
        if (($constraints = $this->constraints[$entityClass] ?? null) === null) {
            $constraints = [];
            foreach ($this->getClassReflection($entityClass)->getProperties() as $property) {
                if ($property->isReadOnly() || $property->isStatic()) {
                    continue;
                }

                $propertyConstraints = [];
                if (($attributes = $property->getAttributes(
                        ConstraintInterface::class, ReflectionAttribute::IS_INSTANCEOF
                    )) !== []
                ) {
                    foreach ($attributes as $attribute) {
                        if ($attribute->getArguments() === []) {
                            $constraint = $this->container->get($attribute->getName());
                        } else {
                            $constraint = $attribute->newInstance();
                            $this->container->injectProperties($constraint);
                        }
                        $propertyConstraints[] = $constraint;
                    }
                }

                if ($propertyConstraints !== []) {
                    $constraints[$property->getName()] = $propertyConstraints;
                }
            }

            $this->constraints[$entityClass] = $constraints;
        }

        return $constraints;
    }

    public function getRelations(string $entityClass): array
    {
        if (($relations = $this->relations[$entityClass] ?? null) === null) {
            $relations = [];
            foreach ($this->getClassReflection($entityClass)->getProperties() as $property) {
                if ($property->isReadOnly() || $property->isStatic()) {
                    continue;
                }
                if (($attributes = $property->getAttributes(
                        RelationInterface::class, ReflectionAttribute::IS_INSTANCEOF
                    )) !== []
                ) {
                    $attribute = $attributes[0];
                    $relation = $property->getName();

                    $parameters = $attribute->getArguments();
                    $parameters['selfEntity'] = $entityClass;
                    $parameters['relation'] = $relation;
                    if (($rType = $property->getType()) !== null && !$rType->isBuiltin()) {
                        $parameters['thatEntity'] = $rType->getName();
                    }

                    $relations[$relation] = $this->container->make($attribute->getName(), $parameters);
                }
            }

            $this->relations[$entityClass] = $relations;
        }

        return $relations;
    }
}