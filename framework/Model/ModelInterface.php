<?php
declare(strict_types=1);

namespace ManaPHP\Model;

use ManaPHP\Query\QueryInterface;

interface ModelInterface
{
    public static function query(?string $alias = null): QueryInterface;

    public static function all(array $filters = [], array $fields = []): array;

    public static function lists(array $fields, array $filters = []): array;

    public static function first(array $filters, array $fields = []): ?static;

    public static function firstOrNew(array $filters): static;

    public static function last(array $filters, array $fields = []): ?static;

    public static function value(array $filters, string $field): mixed;

    public static function values(string $field, array $filters = []): array;

    public static function kvalues(string|array $kv, array $filters = []): array;

    public static function exists(array $filters): bool;

    public function assign(array|object $data, array $fields): static;

    public function validate(array $fields): void;

    public function create(): static;

    public function update(): static;

    public static function updateAll(array $fieldValues, array $filters): int;

    public function delete(): static;

    public static function deleteAll(array $filters): int;

    public function with(array $withs): static;

    public function toArray(): array;

    public function only(array $fields): static;

    public function except(array $fields): static;

    public function getSnapshotData(): array;

    public function getChangedFields(): array;

    public function hasChanged(array $fields): bool;

    public static function select(array $fields = []): QueryInterface;

    public static function where(array $filters): QueryInterface;

    public static function newQuery(): QueryInterface;
}
