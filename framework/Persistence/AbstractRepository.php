<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Query\Paginator;
use ManaPHP\Query\QueryInterface;
use function array_unshift;
use function is_array;
use function is_string;
use function preg_match;

/**
 * @template T of Entity
 * @template-implements  RepositoryInterface<T>
 */
abstract class AbstractRepository implements RepositoryInterface
{
    #[Autowired] protected EntityMetadataInterface $entityMetadata;

    /** @var class-string<T> */
    protected string $entityClass;

    public function __construct()
    {
        $this->entityClass = $this->getEntityClass();
    }

    public function getEntityClass(): string
    {
        if (!isset($this->entityClass)) {
            preg_match('#^(.*)\\\\Repositories\\\\(.*)Repository$#', static::class, $match);
            $this->entityClass = $match[1] . '\\Entities\\' . $match[2];
        }
        return $this->entityClass;
    }

    abstract protected function getEntityManager(): EntityManagerInterface;

    public function select(array $fields = []): QueryInterface
    {
        return $this->getEntityManager()->query($this->entityClass)->select($fields);
    }

    protected function where(array $filters): QueryInterface
    {
        return $this->select()->where($filters);
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
        $withs = [];
        foreach ($fields as $k => $v) {
            if (is_string($k)) {
                $withs[$k] = $v;
                unset($fields[$k]);
            }
        }
        return $this->select($fields)->where($filters)->with($withs)->orderBy($orders)->fetch();
    }

    public function get(int|string $id, array $fields = []): Entity
    {
        $primaryKey = $this->entityMetadata->getPrimaryKey($this->entityClass);

        return $this->firstOrFail([$primaryKey => $id], $fields);
    }

    public function first(array $filters, array $fields = []): ?Entity
    {
        $rs = $this->select($fields)->where($filters)->limit(1)->fetch();
        return $rs[0] ?? null;
    }

    public function firstOrFail(array $filters, array $fields = []): Entity
    {
        if (($entity = $this->first($filters, $fields)) === null) {
            throw new EntityNotFoundException($this->entityClass, $filters);
        }

        return $entity;
    }

    public function value(array $filters, string $field): mixed
    {
        $rs = $this->select([$field])->where($filters)->limit(1)->execute();
        return $rs ? $rs[0][$field] : null;
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
        return $this->where($filters)->orderBy([$field => SORT_ASC])->values($field);
    }

    public function kvalues(string|array $kv, array $filters = []): array
    {
        $dict = [];

        if (is_string($kv)) {
            $key = $this->entityMetadata->getPrimaryKey($this->entityClass);
            $value = $kv;
            foreach ($this->select([$key, $value])->where($filters)->execute() as $row) {
                $dict[$row[$key]] = $row[$value];
            }
        } else {
            $key = array_key_first($kv);
            $fields = $kv[$key];

            if (is_string($fields)) {
                $value = $fields;
                foreach ($this->select([$key, $value])->where($filters)->execute() as $row) {
                    $dict[$row[$key]] = $row[$value];
                }
            } else {
                array_unshift($fields, $key);
                foreach ($this->select($fields)->where($filters)->execute() as $row) {
                    $dict[$row[$key]] = $row;
                }
            }
        }

        return $dict;
    }

    public function exists(array $filters): bool
    {
        return $this->where($filters)->exists();
    }

    public function existsById(int|string $id): bool
    {
        $primaryKey = $this->entityMetadata->getPrimaryKey($this->entityClass);

        return $this->exists([$primaryKey => $id]);
    }

    /**
     * @param T|array $entity
     *
     * @return T
     */
    public function create(Entity|array $entity): Entity
    {
        if (is_array($entity)) {
            $entity = $this->fill($entity);

            $primaryKey = $this->entityMetadata->getPrimaryKey($this->entityClass);
            unset($entity->$primaryKey);
        }

        return $this->getEntityManager()->create($entity);
    }

    /**
     * @param T|array $entity
     *
     * @return T
     */
    public function update(Entity|array $entity): Entity
    {
        $primaryKey = $this->entityMetadata->getPrimaryKey($this->entityClass);

        if (is_array($entity)) {
            $original = $this->get($entity[$primaryKey]);
            $entity = $this->fill($entity);
        } else {
            $original = $this->get($entity[$primaryKey]);
        }

        return $this->getEntityManager()->update($entity, $original);
    }

    /**
     * @param T $entity
     *
     * @return T
     */
    public function delete(Entity $entity): Entity
    {
        return $this->getEntityManager()->delete($entity);
    }

    /**
     * @param int|string $id
     *
     * @return ?T
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function deleteById(int|string $id): ?Entity
    {
        try {
            $entity = $this->get($id);
            $this->delete($entity);
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
    public function fill(array $data): Entity
    {
        $entity = new $this->entityClass;

        foreach ($this->entityMetadata->getFillable($this->entityClass) as $field => $type) {
            if (($value = $data[$field] ?? null) === null) {
                continue;
            }

            if ($type === 'int') {
                $value = (int)$value;
            } elseif ($type === 'float') {
                $value = (float)$value;
            } elseif ($type === 'string') {
                $value = (string)$value;
            }

            $entity->$field = $value;
        }

        return $entity;
    }

    public function paginate(array $fields, array|Restrictions $restrictions, array $orders, Page $page
    ): Paginator {

        $withs = [];
        foreach ($fields as $k => $v) {
            if (is_string($k)) {
                $withs[$k] = $v;
                unset($fields[$k]);
            }
        }

        return $this->select($fields)
            ->where($restrictions)
            ->with($withs)
            ->orderBy($orders)
            ->paginate($page->getPage(), $page->getLimit());
    }

    public function deleteAll(array $filters): int
    {
        return $this->where($filters)->delete();
    }
}