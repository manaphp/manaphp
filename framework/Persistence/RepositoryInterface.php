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
     * @param array|Restrictions $filters
     * @param array              $fields
     * @param array              $orders
     *
     * @return array<T>
     */
    public function all(array|Restrictions $filters = [], array $fields = [], array $orders = []): array;

    /**
     * @param int|string $id
     * @param array      $fields
     *
     * @return T
     */
    public function get(int|string $id, array $fields = []): Entity;

    /**
     * @param array $filters
     * @param array $fields
     *
     * @return ?T
     */
    public function first(array $filters, array $fields = []): ?Entity;

    /**
     * @param array $filters
     * @param array $fields
     *
     * @return T
     */
    public function firstOrFail(array $filters, array $fields = []): Entity;

    public function value(array $filters, string $field): mixed;

    public function valueOrFail(array $filters, string $field): mixed;

    public function valueOrDefault(array $filters, string $field, mixed $default): mixed;

    public function values(string $field, array $filters = []): array;

    public function kvalues(string|array $kv, array $filters = []): array;

    public function exists(array $filters): bool;

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
    public function save(Entity|array $entity): Entity;

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

    public function paginate(array $fields, array|Restrictions $restrictions, array $orders, Page $page
    ): Paginator;

    public function deleteAll(array $filters): int;
}