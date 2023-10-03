<?php
declare(strict_types=1);

namespace ManaPHP\Model;

use ManaPHP\Query\QueryInterface;

interface ModelInterface
{
    public function rules(): array;

    public static function query(?string $alias = null): QueryInterface;

    public static function all(array $filters = [], array $fields = []): array;

    public static function lists(array $fields, array $filters = []): array;

    public static function get(int|string $id, array $fields = []): static;

    public static function first(array $filters, array $fields = []): ?static;

    public static function firstOrFail(array $filters, array $fields = []): static;

    public static function firstOrNew(array $filters, array $data = []): static;

    public static function firstOrCreate(array $filters, array $data = []): static;

    public static function last(array $filters, array $fields = []): ?static;

    public static function value(array $filters, string $field): mixed;

    public static function valueOrFail(array $filters, string $field): mixed;

    public static function valueOrDefault(array $filters, string $field, mixed $default): mixed;

    public static function values(string $field, array $filters = []): array;

    public static function kvalues(string|array $kv, array $filters = []): array;

    public static function exists(array $filters): bool;

    public static function count(array $filters = [], string $field = '*'): int;

    public static function sum(string $field, array $filters = []): mixed;

    public static function max(string $field, array $filters = []): mixed;

    public static function min(string $field, array $filters = []): mixed;

    public static function avg(string $field, array $filters = []): ?float;

    public function assign(array|object $data, array $fields): static;

    public function fill(array $kv): static;

    public static function fillCreate(array $data, array $kv): static;

    public function fillUpdate(array $data): static;

    public function validate(?array $fields = null): void;

    public function save(): static;

    public function create(): static;

    public function update(): static;

    public static function updateAll(array $fieldValues, array $filters): int;

    public function delete(): static;

    public static function deleteAll(array $filters): int;

    public function with(string|array $withs): static;

    public function toArray(): array;

    public function only(array $fields): static;

    public function except(array $fields): static;

    public function getSnapshotData(): array;

    public function getChangedFields(): array;

    public function hasChanged(array $fields): bool;

    public static function select(array $fields = [], ?string $alias = null): QueryInterface;

    public static function where(array $filters): QueryInterface;

    public static function whereCriteria(array $data, array $filters): QueryInterface;

    public function newQuery(): QueryInterface;
}
