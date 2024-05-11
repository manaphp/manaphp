<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Model\ModelInterface;
use ManaPHP\Model\NotFoundException;
use ManaPHP\Query\Paginator;
use ManaPHP\Query\QueryInterface;
use function is_array;
use function preg_match;

/**
 * @template T of ModelInterface
 * @template-implements  RepositoryInterface<T>
 */
class Repository implements RepositoryInterface
{
    #[Autowired] protected EntityFillerInterface $entityFiller;

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
     *
     * @return array<T>
     */
    public function all(array $filters = [], array $fields = []): array
    {
        return $this->entityClass::all($filters, $fields);
    }

    public function lists(array $fields, array $filters = []): array
    {
        return $this->entityClass::lists($fields, $filters);
    }

    public function get(int|string $id, array $fields = []): object
    {
        return $this->entityClass::get($id, $fields);
    }

    public function first(array $filters, array $fields = []): ?object
    {
        return $this->entityClass::first($filters, $fields);
    }

    public function firstOrFail(array $filters, array $fields = []): object
    {
        return $this->entityClass::firstOrFail($filters, $fields);
    }

    public function firstOrNew(array $filters): object
    {
        return $this->entityClass::firstOrNew($filters);
    }

    public function value(array $filters, string $field): mixed
    {
        return $this->entityClass::value($filters, $field);
    }

    public function valueOrFail(array $filters, string $field): mixed
    {
        return $this->entityClass::valueOrFail($filters, $field);
    }

    public function valueOrDefault(array $filters, string $field, mixed $default): mixed
    {
        return $this->entityClass::valueOrDefault($filters, $field, $default);
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

    /**
     * @param T $entity
     *
     * @return T
     */
    public function save(object $entity): object
    {
        return $entity->save();
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
        } catch (NotFoundException) {
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

    public function paginate(CriteriaInterface $criteria): Paginator
    {
        /** @var QueryInterface $query */
        $query = $this->entityClass::select($criteria->getSelect())
            ->where($criteria->getWhere());
        if (($whereCriteria = $criteria->getWhereCriteria()) !== []) {
            $query->whereCriteria($whereCriteria[0], $whereCriteria[1]);
        }

        return $query->orderBy($criteria->getOrderBy())
            ->paginate($criteria->getPage(), $criteria->getLimit());
    }

    public function deleteAll(array $filters): int
    {
        return $this->entityClass::deleteAll($filters);
    }
}