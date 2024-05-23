<?php
declare(strict_types=1);

namespace ManaPHP\Mongodb;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\RuntimeException;
use ManaPHP\Persistence\AbstractEntityManager;
use ManaPHP\Persistence\Entity;
use ManaPHP\Persistence\Event\EntityCreated;
use ManaPHP\Persistence\Event\EntityCreating;
use ManaPHP\Persistence\Event\EntityDeleted;
use ManaPHP\Persistence\Event\EntityDeleting;
use ManaPHP\Persistence\Event\EntityUpdated;
use ManaPHP\Persistence\Event\EntityUpdating;
use MongoDB\BSON\ObjectId;
use function gettype;
use function is_bool;
use function is_float;
use function is_int;
use function is_scalar;
use function is_string;
use function strlen;

class EntityManager extends AbstractEntityManager implements EntityManagerInterface
{
    #[Autowired] protected MongodbConnectorInterface $mongodbConnector;

    #[Config] protected string $queryClass = 'ManaPHP\Mongodb\Query';

    protected array $fieldTypes = [];

    /**
     * @return Query
     */
    public function newQuery(): Query
    {
        return $this->maker->make($this->queryClass);
    }

    protected static bool $_defaultAllowNullValue = false;
    public mixed $_id;

    public static function setDefaultAllowNullValue(bool $allow): void
    {
        self::$_defaultAllowNullValue = $allow;
    }

    /**
     * bool, int, float, string, array, objectid
     *
     * @return array =entity_var(new static)
     */
    public function fieldTypes(string $entityClass): array
    {
        if (($types = $this->fieldTypes[$entityClass] ?? null) === null) {
            list($connection, $collection) = $this->sharding->getAnyShard($entityClass);

            $mongodb = $this->mongodbConnector->get($connection);
            if (!$docs = $mongodb->fetchAll($collection, [], ['limit' => 1])) {
                throw new RuntimeException(['`{collection}` collection has none record', 'collection' => $collection]);
            }

            $types = [];
            foreach ($docs[0] as $field => $value) {
                $type = gettype($value);
                if ($type === 'integer') {
                    $types[$field] = 'int';
                } elseif ($type === 'string') {
                    $types[$field] = 'string';
                } elseif ($type === 'double') {
                    $types[$field] = 'float';
                } elseif ($type === 'boolean') {
                    $types[$field] = 'bool';
                } elseif ($type === 'array') {
                    $types[$field] = 'array';
                } elseif ($value instanceof ObjectId) {
                    if ($field === '_id') {
                        continue;
                    }
                    $types[$field] = 'objectid';
                } else {
                    throw new RuntimeException(['`{field}` field value type can not be infer.', 'field' => $field]);
                }
            }

            return $this->fieldTypes[$entityClass] = $types;
        } else {
            return $types;
        }
    }

    public function isAllowNullValue(): bool
    {
        return self::$_defaultAllowNullValue;
    }

    public function normalizeValue(string $type, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($type === 'string') {
            return is_string($value) ? $value : (string)$value;
        } elseif ($type === 'int') {
            return is_int($value) ? $value : (int)$value;
        } elseif ($type === 'float') {
            return is_float($value) ? $value : (float)$value;
        } elseif ($type === 'objectid') {
            return is_scalar($value) ? new ObjectID($value) : $value;
        } elseif ($type === 'bool') {
            return is_bool($value) ? $value : (bool)$value;
        } elseif ($type === 'array') {
            return (array)$value;
        } else {
            throw new MisuseException(['`{1}` type is not supported', $type]);
        }
    }

    /**
     * @param Entity $entity
     *
     * @return Entity
     */
    public function create(Entity $entity): Entity
    {
        $entityClass = $entity::class;

        $this->autoFiller->fillCreated($entity);

        $fields = $this->entityMetadata->getFields($entityClass);

        $this->validate($entity, $this->entityMetadata->getFillable($entityClass));

        if ($entity->_id) {
            if (is_string($entity->_id) && strlen($entity->_id) === 24) {
                $entity->_id = new ObjectID($entity->_id);
            }
        } else {
            $entity->_id = new ObjectID();
        }

        $allowNull = $this->isAllowNullValue();
        foreach ($this->fieldTypes($entityClass) as $field => $type) {
            if ($field === '_id') {
                continue;
            }

            if (isset($entity->$field)) {
                if (is_scalar($entity->$field)) {
                    $entity->$field = $this->normalizeValue($type, $entity->$field);
                }
            } else {
                $entity->$field = $allowNull ? null : $this->normalizeValue($type, '');
            }
        }

        list($connection, $collection) = $this->sharding->getUniqueShard($entityClass, $entity);

        $this->dispatchEvent(new EntityCreating($entity));

        $fieldValues = [];
        foreach ($fields as $field) {
            $fieldValues[$field] = $entity->$field;
        }

        $fieldValues['_id'] = $entity->_id;

        $mongodb = $this->mongodbConnector->get($connection);
        $mongodb->insert($collection, $fieldValues);

        $this->dispatchEvent(new EntityCreated($entity));

        return $entity;
    }

    public function update(Entity $entity, Entity $original): Entity
    {
        $entityClass = $entity::class;
        $primaryKey = $this->entityMetadata->getPrimaryKey($entityClass);

        if (!isset($entity->$primaryKey)) {
            throw new MisuseException('missing primary key value');
        }

        $fields = $this->entityMetadata->getFields($entityClass);

        if ($entity->$primaryKey !== $original->$primaryKey) {
            throw new MisuseException('updating entity primary key value is not support');
        }

        $changedFields = [];
        foreach ($fields as $field) {
            if (isset($entity->$field) && $entity->$field !== $original->$field) {
                $changedFields[] = $field;
            }
        }

        if ($changedFields === []) {
            return $entity;
        }

        foreach ($fields as $field) {
            if (!isset($entity->$field)) {
                $entity->$field = $original->$field;
            }
        }

        $this->validate($entity, $changedFields);

        $this->autoFiller->fillUpdated($entity);
        list($connection, $collection) = $this->sharding->getUniqueShard($entityClass, $entity);

        $this->dispatchEvent(new EntityUpdating($entity, $original));

        $fieldValues = [];
        foreach ($fields as $field) {
            if (isset($original->$field) && $entity->$field !== $original->$field) {
                $fieldValues[$field] = $entity->$field;
            }
        }

        $mongodb = $this->mongodbConnector->get($connection);
        $mongodb->update($collection, $fieldValues, [$primaryKey => $entity->$primaryKey]);

        $this->dispatchEvent(new EntityUpdated($entity, $original));

        return $entity;
    }

    public function delete(Entity $entity): Entity
    {
        $entityClass = $entity::class;
        $primaryKey = $this->entityMetadata->getPrimaryKey($entityClass);

        if (!isset($entity->$primaryKey)) {
            throw new MisuseException('missing primary key value');
        }

        list($connection, $table) = $this->sharding->getUniqueShard($entityClass, $entity);

        $this->dispatchEvent(new EntityDeleting($entity));

        $mongodb = $this->mongodbConnector->get($connection);

        $mongodb->delete($table, [$primaryKey => $entity->$primaryKey]);

        $this->dispatchEvent(new EntityDeleted($entity));

        return $entity;
    }

    public function aggregateEx(string $entityClass, array $pipeline, array $options = []): array
    {
        list($connection, $collection) = $this->sharding->getUniqueShard($entityClass, []);

        return $this->mongodbConnector->get($connection)->aggregate(
            $collection, $pipeline, $options
        );
    }

    public function normalizeDocument(string $entityClass, array $document): array
    {
        $allowNull = $this->isAllowNullValue();
        $fieldTypes = $this->fieldTypes($entityClass);

        foreach ($fieldTypes as $field => $type) {
            if (isset($document[$field])) {
                $document[$field] = $this->normalizeValue($type, $document[$field]);
            } elseif ($field !== '_id') {
                $document[$field] = $allowNull ? null : $this->normalizeValue($type, '');
            }
        }

        return $document;
    }
}