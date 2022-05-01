<?php
declare(strict_types=1);

namespace ManaPHP\Data;

use ManaPHP\Data\Relation\BelongsTo;
use ManaPHP\Data\Relation\HasMany;
use ManaPHP\Data\Relation\HasManyOthers;
use ManaPHP\Data\Relation\HasManyToMany;
use ManaPHP\Data\Relation\HasOne;

interface ModelInterface
{
    public function dateFormat(string $field): string;

    public function intFields(): ?array;

    public function rules(): array;

    public static function query(?string $alias = null): QueryInterface;

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param array  $filters
     * @param ?array $options
     * @param ?array $fields
     *
     * @return  static[]
     */
    public static function all(array $filters = [], ?array $options = null, ?array $fields = null): array;

    public static function paginate(array $filters = [], ?array $options = null, ?array $fields = null): Paginator;

    public static function lists(string|array $fields, ?array $filters = null): array;

    public static function dict(string|array $kv, ?array $filters = null): array;

    public static function get(int|string $id, ?int $ttl = null): static;

    public static function first(array $filters, ?array $fields = null): ?static;

    public static function firstOrFail(array $filters, ?array $fields = null): static;

    public static function rId(): int|string;

    public static function rGet(?array $fields = null): static;

    public static function last(?array $filters = null, ?array $fields = null): ?static;

    public static function value(array $filters, string $field, ?int $ttl = null): mixed;

    public static function valueOrFail(array $filters, string $field, ?int $ttl = null): mixed;

    public static function valueOrDefault(array $filters, string $field, mixed $default): mixed;

    public static function values(string $field, ?array $filters = null): array;

    public static function kvalues(string $field, ?array $filters = null): array;

    public static function exists(array $filters): bool;

    public static function aggregate(array $filters, array $aggregation, null|string|array $options = null): array;

    public static function count(?array $filters = null, string $field = '*'): int;

    public static function sum(string $field, ?array $filters = null): mixed;

    public static function max(string $field, ?array $filters = null): mixed;

    public static function min(string $field, ?array $filters = null): mixed;

    public static function avg(string $field, ?array $filters = null): ?float;

    public function load(?array $fields = null): static;

    public function assign(array|object $data, array $fields): static;

    public function validate(?array $fields = null): void;

    public function validateField(string $field, ?array $rules = null): void;

    public function save(?array $fields = null): static;

    public function create(): static;

    public static function rCreate(?array $fields = null): static;

    public function update(): static;

    public static function rUpdate(?array $fields = null): static;

    public static function updateAll(array $fieldValues, array $filters): int;

    public function delete(): static;

    public static function rDelete(): ?static;

    public static function deleteAll(array $filters): int;

    public function with(string|array $withs): static;

    public function toArray(): array;

    public function only(array $fields): static;

    public function except(array $fields): static;

    public function getSnapshotData(): array;

    public function getChangedFields(): array;

    public function hasChanged(array $fields): bool;

    public function refresh(float $interval, ?array $fields = null): static;

    public static function select(?array $fields = null, ?string $alias = null): QueryInterface;

    public static function where(?array $filters = null): QueryInterface;

    public static function search(array $filters): QueryInterface;

    public function newQuery(): QueryInterface;

    public function belongsTo(string $thatModel, ?string $thisField = null): BelongsTo;

    public function hasOne(string $thatModel, ?string $thatField = null): HasOne;

    public function hasMany(string $thatModel, ?string $thatField = null): HasMany;

    public function hasManyToMany(string $thatModel, string $pivotModel): HasManyToMany;

    /**
     * @param string  $thatModel
     * @param ?string $thisFilter =model_field(new static)
     *
     * @return \ManaPHP\Data\Relation\HasManyOthers
     */
    public function hasManyOthers(string $thatModel, ?string $thisFilter = null): HasManyOthers;

    public function belongsToMany(string $thatModel, string $pivotModel): HasManyToMany;
}
