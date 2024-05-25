<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

use ManaPHP\Query\Paginator;

/**
 * @template T of Entity
 */
interface RepositoryInterface
{
    /**
     * @return class-string<T>
     */
    public function getEntityClass(): string;

    /**
     * @param array|Restrictions $restrictions
     * @param array              $fields
     * @param array              $orders
     *
     * @return array<T>
     */
    public function all(array|Restrictions $restrictions = [], array $fields = [], array $orders = []): array;

    public function paginate(array|Restrictions $restrictions, array $fields, array $orders, Page $page): Paginator;

    /**
     * @param int|string $id
     * @param array      $fields
     *
     * @return T
     */
    public function get(int|string $id, array $fields = []): Entity;

    /**
     * @param array|Restrictions $restrictions
     * @param array              $fields
     *
     * @return ?T
     */
    public function first(array|Restrictions $restrictions, array $fields = []): ?Entity;

    /**
     * @param array|Restrictions $restrictions
     * @param array              $fields
     *
     * @return T
     */
    public function firstOrFail(array|Restrictions $restrictions, array $fields = []): Entity;

    public function value(array|Restrictions $restrictions, string $field): mixed;

    public function valueOrFail(array|Restrictions $restrictions, string $field): mixed;

    public function valueOrDefault(array|Restrictions $restrictions, string $field, mixed $default): mixed;

    public function values(string $field, array|Restrictions $restrictions = []): array;

    public function dict(array|Restrictions $restrictions, string|array $kv): array;

    public function exists(array|Restrictions $restrictions): bool;

    public function existsById(int|string $id): bool;

    /**
     * @param array $data
     *
     * @return T
     */
    public function fill(array $data): Entity;

    /**
     * @param T|array $entity
     *
     * @return T
     */
    public function create(Entity|array $entity): Entity;

    /**
     * @param T|array $entity
     *
     * @return T
     */
    public function update(Entity|array $entity): Entity;

    /**
     * @param T $entity
     *
     * @return T
     */
    public function delete(Entity $entity): Entity;

    /**
     * @param int|string $id
     *
     * @return ?T
     */
    public function deleteById(int|string $id): ?Entity;

    public function deleteAll(array|Restrictions $restrictions): int;
}