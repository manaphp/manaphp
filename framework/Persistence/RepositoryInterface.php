<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

use ManaPHP\Query\Paginator;

/**
 * @template T
 */
interface RepositoryInterface
{
    /**
     * @return class-string<T>
     */
    public function getEntityClass(): string;

    /**
     * @param array $filters
     * @param array $fields
     *
     * @return array<T>
     */
    public function all(array $filters = [], array $fields = []): array;

    public function lists(array $fields, array $filters = []): array;

    /**
     * @param int|string $id
     * @param array      $fields
     *
     * @return T
     */
    public function get(int|string $id, array $fields = []): object;

    /**
     * @param array $filters
     * @param array $fields
     *
     * @return ?T
     */
    public function first(array $filters, array $fields = []): ?object;

    /**
     * @param array $filters
     * @param array $fields
     *
     * @return T
     */
    public function firstOrFail(array $filters, array $fields = []): object;

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
    public function fill(array $data): object;

    /**
     * @param T|array $entity
     *
     * @return T
     */
    public function save(object|array $entity): object;

    /**
     * @param T|array $entity
     *
     * @return T
     */
    public function create(object|array $entity): object;

    /**
     * @param T|array $entity
     *
     * @return T
     */
    public function update(object|array $entity): object;

    /**
     * @param T $entity
     *
     * @return T
     */
    public function delete(object $entity): object;

    /**
     * @param int|string $id
     *
     * @return ?T
     */
    public function deleteById(int|string $id): ?object;

    public function applyCriteria(CriteriaInterface $criteria): Paginator;

    public function deleteAll(array $filters): int;
}