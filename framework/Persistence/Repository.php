<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Model\ModelInterface;
use ManaPHP\Model\ModelsInterface;
use ManaPHP\Query\Paginator;
use function is_array;
use function preg_match;

/**
 * @template T of ModelInterface
 * @template-implements  RepositoryInterface<T>
 */
class Repository implements RepositoryInterface
{
    #[Autowired] protected EntityFillerInterface $entityFiller;
    #[Autowired] protected ModelsInterface $models;

    /** @var class-string<T>|ModelInterface */
    protected string $entityClass;

    public function __construct()
    {
        $this->entityClass = $this->getEntityClass();
    }

    public function getEntityClass(): string
    {
        if (!isset($this->entityClass)) {
            preg_match('#^(.*)\\\\Repositories\\\\(.*)Repository$#', static::class, $match);
            $this->entityClass = $match[1] . '\\Models\\' . $match[2];
        }
        return $this->entityClass;
    }

    /**
     * @param array $filters
     * @param array $fields
     * @param array $orders
     *
     * @return array<T>
     */
    public function all(array|Restrictions $filters = [], array $fields = [], array $orders = []): array
    {
        return $this->entityClass::all($filters, $fields, $orders);
    }

    public function lists(array $fields, array $filters = []): array
    {
        return $this->entityClass::lists($fields, $filters);
    }

    public function get(int|string $id, array $fields = []): object
    {
        $primaryKey = $this->models->getPrimaryKey($this->entityClass);

        return $this->firstOrFail([$primaryKey => $id], $fields);
    }

    public function first(array $filters, array $fields = []): ?object
    {
        return $this->entityClass::first($filters, $fields);
    }

    public function firstOrFail(array $filters, array $fields = []): object
    {
        if (($entity = $this->first($filters, $fields)) === null) {
            throw new EntityNotFoundException($this->entityClass, $filters);
        }

        return $entity;
    }

    public function value(array $filters, string $field): mixed
    {
        return $this->entityClass::value($filters, $field);
    }

    public function valueOrFail(array $filters, string $field): mixed
    {
        if (($value = $this->value($filters, $field)) === null) {
            throw new EntityNotFoundException($this->entityClass, $filters);
        } else {
            return $value;
        }
    }

    public function valueOrDefault(array $filters, string $field, mixed $default): mixed
    {
        return $this->value($filters, $field) ?? $default;
    }

    public function values(string $field, array $filters = []): array
    {
        return $this->entityClass::values($field, $filters);
    }

    public function kvalues(string|array $kv, array $filters = []): array
    {
        return $this->entityClass::kvalues($kv, $filters);
    }

    public function exists(array $filters): bool
    {
        return $this->entityClass::exists($filters);
    }

    public function existsById(int|string $id): bool
    {
        $primaryKey = $this->models->getPrimaryKey($this->entityClass);

        return $this->entityClass::exists([$primaryKey => $id]);
    }

    /**
     * @param T|array $entity
     *
     * @return T
     */
    public function save(object|array $entity): object
    {
        $primaryKey = $this->models->getPrimaryKey($this->entityClass);

        if (is_array($entity)) {
            if (!isset($entity[$primaryKey]) || !$this->existsById($entity[$primaryKey])) {
                return $this->create($entity);
            }
        } else {
            if (!isset($entity->$primaryKey) || !$this->existsById($entity->$primaryKey)) {
                return $this->create($entity);
            }
        }

        return $this->update($entity);
    }

    /**
     * @param T|array $entity
     *
     * @return T
     */
    public function create(object|array $entity): object
    {
        if (is_array($entity)) {
            return $this->fill($entity)->create();
        } else {
            return $entity->create();
        }
    }

    /**
     * @param T|array $entity
     *
     * @return T
     */
    public function update(object|array $entity): object
    {
        if (is_array($entity)) {
            return $this->fill($entity)->update();
        } else {
            return $entity->update();
        }
    }

    /**
     * @param T $entity
     *
     * @return T
     */
    public function delete(object $entity): object
    {
        return $entity->delete();
    }

    /**
     * @param int|string $id
     *
     * @return ?T
     */
    public function deleteById(int|string $id): ?object
    {
        try {
            $entity = $this->get($id);
            $entity->delete();
            return $entity;
        } catch (EntityNotFoundException) {
            return null;
        }
    }

    /**
     * @param array $data
     *
     * @return T
     */
    public function fill(array $data): object
    {
        return $this->entityFiller->fill(new $this->entityClass(), $data);
    }

    public function paginate(array $fields, array|Restrictions $restrictions, array $orders, Page $page
    ): Paginator {
        return $this->entityClass::select($fields)
            ->where($restrictions)
            ->orderBy($orders)
            ->paginate($page->getPage(), $page->getLimit());
    }

    public function deleteAll(array $filters): int
    {
        return $this->entityClass::deleteAll($filters);
    }
}