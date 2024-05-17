<?php
declare(strict_types=1);

namespace ManaPHP\Db;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Persistence\AbstractEntityManager;
use ManaPHP\Persistence\Entity;
use ManaPHP\Persistence\Entity\Lifecycle;
use ManaPHP\Persistence\Event\EntityCreated;
use ManaPHP\Persistence\Event\EntityCreating;
use ManaPHP\Persistence\Event\EntityDeleted;
use ManaPHP\Persistence\Event\EntityDeleting;
use ManaPHP\Persistence\Event\EntityUpdated;
use ManaPHP\Persistence\Event\EntityUpdating;
use function array_key_exists;

class EntityManager extends AbstractEntityManager implements EntityManagerInterface
{
    #[Autowired] protected DbConnectorInterface $dbConnector;

    #[Config] protected string $queryClass = 'ManaPHP\Db\Query';

    /**
     * @return Query <static>
     */
    public function newQuery(): Query
    {
        return $this->maker->make($this->queryClass);
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

        $primaryKey = $this->entityMetadata->getPrimaryKey($entityClass);

        list($connection, $table) = $this->sharding->getUniqueShard($entityClass, $entity);

        $entity->onLifecycle(Lifecycle::Creating);
        $this->eventDispatcher->dispatch(new EntityCreating($entity));

        $fieldValues = [];
        $defaultValueFields = [];
        foreach ($fields as $field) {
            if (isset($entity->$field)) {
                $fieldValues[$field] = $entity->$field;
            } elseif ($field !== $primaryKey) {
                $defaultValueFields[] = $field;
            }
        }

        foreach ($this->entityMetadata->getColumnMap($entityClass) as $propery => $column) {
            if (array_key_exists($propery, $fieldValues)) {
                $fieldValues[$column] = $fieldValues[$propery];
                unset($fieldValues[$propery]);
            }
        }

        $db = $this->dbConnector->get($connection);
        if (!isset($entity->$primaryKey)) {
            $entity->$primaryKey = (int)$db->insert($table, $fieldValues, true);
        } else {
            $db->insert($table, $fieldValues);
        }

        if ($defaultValueFields) {
            $query = $this->query($entityClass)->select($defaultValueFields)->where(
                [$primaryKey => $entity->$primaryKey]
            );
            if ($r = $query->execute()) {
                foreach ($r[0] as $field => $value) {
                    $entity->$field = $value;
                }
            }
        }

        $entity->onLifecycle(Lifecycle::Created);
        $this->eventDispatcher->dispatch(new EntityCreated($entity));

        $entity->_snapshot = $entity->toArray();

        return $entity;
    }

    /**
     * @param Entity $entity
     *
     * @return Entity
     */
    public function update(Entity $entity): Entity
    {
        $entityClass = $entity::class;
        $primaryKey = $this->entityMetadata->getPrimaryKey($entityClass);

        if (!isset($entity->$primaryKey)) {
            throw new MisuseException('missing primary key value');
        }

        $this->_snapshot[$primaryKey] ??= $entity->$primaryKey;

        $snapshot = $entity->_snapshot;

        /** @noinspection TypeUnsafeComparisonInspection */
        if ($entity->$primaryKey != $snapshot[$primaryKey]) {
            throw new MisuseException('updating entity primary key value is not support');
        }

        if (($changedFields = $entity->getChangedFields()) === []) {
            return $entity;
        }

        $this->validate($entity, $changedFields);

        $this->autoFiller->fillUpdated($entity);

        list($connection, $table) = $this->sharding->getUniqueShard($entityClass, $entity);

        $entity->onLifecycle(Lifecycle::Updating);
        $this->eventDispatcher->dispatch(new EntityUpdating($entity));

        $fieldValues = [];
        foreach ($this->entityMetadata->getFields($entityClass) as $field) {
            if (!isset($entity->$field)) {
                if (isset($snapshot[$field])) {
                    $fieldValues[$field] = null;
                }
            } elseif (!isset($snapshot[$field]) || $snapshot[$field] !== $entity->$field) {
                $fieldValues[$field] = $entity->$field;
            }
        }

        $columnMap = $this->entityMetadata->getColumnMap($entityClass);
        foreach ($columnMap as $property => $column) {
            if (array_key_exists($property, $fieldValues)) {
                $fieldValues[$column] = $fieldValues[$property];
                unset($fieldValues[$property]);
            }
        }

        $db = $this->dbConnector->get($connection);
        $db->update($table, $fieldValues, [$columnMap[$primaryKey] ?? $primaryKey => $entity->$primaryKey]);

        $entity->onLifecycle(Lifecycle::Updated);
        $this->eventDispatcher->dispatch(new EntityUpdated($entity));

        $entity->_snapshot = $entity->toArray();

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

        $entity->onLifecycle(Lifecycle::Deleting);
        $this->eventDispatcher->dispatch(new EntityDeleting($entity));

        $db = $this->dbConnector->get($connection);

        $db->delete($table, [$primaryKey => $entity->$primaryKey]);

        $entity->onLifecycle(Lifecycle::Deleted);
        $this->eventDispatcher->dispatch(new EntityDeleted($entity));

        return $entity;
    }
}
