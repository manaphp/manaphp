<?php
declare(strict_types=1);

namespace ManaPHP\Query;

use ManaPHP\Model\ModelInterface;

/**
 * @template Model
 */
interface QueryInterface
{
    public function setModel(string $model): static;

    public function getModel(): ?string;

    public function shard(callable $strategy): static;

    public function from(string $table, ?string $alias = null): static;

    /**
     * @param array $fields =model_fields(new Model)
     *
     * @return static
     */
    public function select(array $fields): static;

    public function distinct(bool $distinct = true): static;

    /**
     * Appends a condition to the current conditions using a AND operator
     *
     * @param array $filters =model_var(new Model)
     *
     * @return static
     */
    public function where(array $filters): static;

    /**
     * @param string $field =model_field(new Model)
     * @param mixed  $value
     *
     * @return static
     */
    public function whereEq(string $field, mixed $value): static;

    /**
     * @param string $field =model_field(new Model)
     * @param string $operator
     * @param mixed  $value
     *
     * @return static
     */
    public function whereCmp(string $field, string $operator, mixed $value): static;

    /**
     * @param string $field =model_field(new Model)
     * @param int    $divisor
     * @param int    $remainder
     *
     * @return static
     */
    public function whereMod(string $field, int $divisor, int $remainder): static;

    public function whereExpr(string $expr, ?array $bind = null): static;

    /**
     * @param array $data
     * @param array $filters =model_var(new Model)
     *
     * @return static
     */
    public function whereCriteria(array $data, array $filters): static;

    /**
     * Appends a BETWEEN condition to the current conditions
     *
     * @param string           $field =model_field(new Model)
     * @param int|float|string $min
     * @param int|float|string $max
     *
     * @return static
     */
    public function whereBetween(string $field, mixed $min, mixed $max): static;

    /**
     * Appends a NOT BETWEEN condition to the current conditions
     *
     * @param string           $field =model_field(new Model)
     * @param int|float|string $min
     * @param int|float|string $max
     *
     * @return static
     */
    public function whereNotBetween(string $field, mixed $min, mixed $max): static;

    /**
     * @param string     $field =model_field(new Model)
     * @param int|string $min
     * @param int|string $max
     *
     * @return static
     */
    public function whereDateBetween(string $field, mixed $min, mixed $max): static;

    /**
     * Appends an IN condition to the current conditions
     *
     * @param string $field =model_field(new Model)
     * @param array  $values
     *
     * @return static
     */
    public function whereIn(string $field, array $values): static;

    /**
     * Appends a NOT IN condition to the current conditions
     *
     * @param string $field =model_field(new Model)
     * @param array  $values
     *
     * @return static
     */
    public function whereNotIn(string $field, array $values): static;

    /**
     * @param string $field =model_field(new Model)
     * @param string $value
     *
     * @return static
     */
    public function whereInset(string $field, string $value): static;

    /**
     * @param string $field =model_field(new Model)
     * @param string $value
     *
     * @return static
     */
    public function whereNotInset(string $field, string $value): static;

    /**
     * @param string|array $fields =model_fields(new Model) ?: model_field(new Model)
     * @param string       $value
     *
     * @return static
     */
    public function whereContains(string|array $fields, string $value): static;

    /**
     * @param string|array $fields =model_fields(new Model) ?: model_field(new Model)
     * @param string       $value
     *
     * @return static
     */
    public function whereNotContains(string|array $fields, string $value): static;

    /**
     * @param string|array $fields =model_fields(new Model) ?: model_field(new Model)
     * @param string       $value
     * @param ?int         $length
     *
     * @return static
     */
    public function whereStartsWith(string|array $fields, string $value, ?int $length = null): static;

    /**
     * @param string|array $fields =model_fields(new Model) ?: model_field(new Model)
     * @param string       $value
     * @param ?int         $length
     *
     * @return static
     */
    public function whereNotStartsWith(string|array $fields, string $value, ?int $length = null): static;

    /**
     * @param string|array $fields =model_fields(new Model)?: model_field(new Model)
     * @param string       $value
     *
     * @return static
     */
    public function whereEndsWith(string|array $fields, string $value): static;

    /**
     * @param string|array $fields =model_fields(new Model) ?: model_field(new Model)
     * @param string       $value
     *
     * @return static
     */
    public function whereNotEndsWith(string|array $fields, string $value): static;

    /**
     * @param string|array $fields =model_fields(new Model) ?: model_field(new Model)
     * @param string       $value
     *
     * @return static
     */
    public function whereLike(string|array $fields, string $value): static;

    /**
     * @param string|array $fields =model_fields(new Model) ?: model_field(new Model)
     * @param string       $value
     *
     * @return static
     */
    public function whereNotLike(string|array $fields, string $value): static;

    /**
     * @param string $field =model_field(new Model)
     * @param string $regex
     * @param string $flags
     *
     * @return static
     */
    public function whereRegex(string $field, string $regex, string $flags = ''): static;

    /**
     * @param string $field =model_field(new Model)
     * @param string $regex
     * @param string $flags
     *
     * @return static
     */
    public function whereNotRegex(string $field, string $regex, string $flags = ''): static;

    /**
     * @param string $field =model_field(new Model)
     *
     * @return static
     */
    public function whereNull(string $field): static;

    /**
     * @param string $field =model_field(new Model)
     *
     * @return static
     */
    public function whereNotNull(string $field): static;

    /**
     * Sets a ORDER BY condition clause
     *
     * @param string|array $orderBy =model_var(new Model) ?: model_field(new Model) ?: [$k=>SORT_ASC, $k=>SORT_DESC]
     *
     * @return static
     */
    public function orderBy(string|array $orderBy): static;

    /**
     * @param callable|string|array $indexBy =model_field(new Model)
     *
     * @return static
     */
    public function indexBy(callable|string|array $indexBy): static;

    /**
     * Sets a GROUP BY clause
     *
     * @param string|array $groupBy =model_var(new Model) ?: model_field(new Model)
     *
     * @return static
     */
    public function groupBy(string|array $groupBy): static;

    public function with(array $with): static;

    public function limit(int $limit, ?int $offset = null): static;

    public function forceUseMaster(bool $forceUseMaster = true): static;

    public function execute(): array;

    public function aggregate(array $expr): array;

    public function paginate(int $page, int $size = 10): Paginator;

    public function map(callable $map): static;

    public function setFetchType(bool $multiple): static;

    /**
     * @return ModelInterface[]|ModelInterface|array|null
     */
    public function fetch(): mixed;

    public function first(): ?array;

    public function get(): array;

    public function all(): array;

    /**
     * @param string $field =model_field(new Model)
     * @param mixed  $default
     *
     * @return mixed
     */
    public function value(string $field, mixed $default = null): mixed;

    /**
     * @param string $field =model_field(new Model)
     *
     * @return array
     */
    public function values(string $field): array;

    public function exists(): bool;

    /**
     * @param string $field =model_field(new Model)
     *
     * @return int
     */
    public function count(string $field = '*'): int;

    /**
     * @param string $field =model_field(new Model)
     *
     * @return int|float|null
     */
    public function sum(string $field): mixed;

    /**
     * @param string $field =model_field(new Model)
     *
     * @return int|float|null
     */
    public function max(string $field): mixed;

    /**
     * @param string $field =model_field(new Model)
     *
     * @return int|float|null
     */
    public function min(string $field): mixed;

    /**
     * @param string $field =model_field(new Model)
     *
     * @return float|null
     */
    public function avg(string $field): ?float;

    /**
     * @return int
     */
    public function delete(): int;

    /**
     * @param array $fieldValues =model_var(new Model)
     *
     * @return int
     */
    public function update(array $fieldValues): int;

    public function when(callable $call): static;

    /**
     * @param string     $field =model_field(new Model)
     * @param string|int $date
     *
     * @return static
     */
    public function whereDate(string $field, int|string $date): static;

    /**
     * @param string     $field =model_field(new Model)
     * @param string|int $date
     *
     * @return static
     */
    public function whereMonth(string $field, int|string $date): static;

    /**
     * @param string     $field =model_field(new Model)
     * @param string|int $date
     *
     * @return static
     */
    public function whereYear(string $field, int|string $date): static;

    public function where1v1(string $id, string $value): static;

    public function join(string $table, ?string $condition = null, ?string $alias = null, ?string $type = null): static;

    public function innerJoin(string $table, ?string $condition = null, ?string $alias = null): static;

    public function leftJoin(string $table, ?string $condition = null, ?string $alias = null): static;

    public function rightJoin(string $table, ?string $condition = null, ?string $alias = null): static;

    public function whereRaw(string $filter, ?array $bind = null): static;

    public function having(string|array $having, array $bind = []): static;

    public function getSql(): string;
}