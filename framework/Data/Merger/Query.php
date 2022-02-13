<?php
declare(strict_types=1);

namespace ManaPHP\Data\Merger;

use ManaPHP\Data\AbstractQuery;
use ManaPHP\Data\ModelInterface;
use ManaPHP\Data\QueryInterface;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Helper\Arr;

/**
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
class Query extends AbstractQuery
{
    protected array $queries;

    public function __construct(array $queries, null|string|array $fields = null)
    {
        $this->setQueries($queries)->select($fields);
    }

    public function __clone()
    {
        foreach ($this->queries as $k => $v) {
            $this->queries[$k] = clone $v;
        }
    }

    /**
     * @param string[]|\ManaPHP\Data\ModelInterface[]|\ManaPHP\Data\QueryInterface[] $queries
     *
     * @return static
     */
    public function setQueries(mixed $queries): static
    {
        foreach ($queries as $id => $query) {
            if (is_string($query)) {
                $query = $this->container->make($query);
            }

            if ($query instanceof QueryInterface) {
                $this->queries[$id] = $query;
            } elseif ($query instanceof ModelInterface) {
                $this->queries[$id] = $query::query();
            } else {
                throw new MisuseException('');
            }
        }

        return $this;
    }

    /**
     * @return \ManaPHP\Data\QueryInterface[]
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    public function setDb(mixed $db): static
    {
        throw new NotSupportedException(__METHOD__);
    }

    public function shard(?callable $strategy = null): static
    {
        throw new NotSupportedException(__METHOD__);
    }

    public function from(string $table, ?string $alias = null): static
    {
        throw new NotSupportedException(__METHOD__);
    }

    public function getShards(): array
    {
        throw new NotSupportedException(__METHOD__);
    }

    public function getUniqueShard(): array
    {
        throw new NotSupportedException(__METHOD__);
    }

    public function setModel(ModelInterface $model): static
    {
        $this->model = $model;

        foreach ($this->queries as $query) {
            $query->setModel($model);
        }

        return $this;
    }

    public function select(string|array $fields): static
    {
        foreach ($this->queries as $query) {
            $query->select($fields);
        }

        return $this;
    }

    public function distinct(bool $distinct = true): static
    {
        foreach ($this->queries as $query) {
            $query->distinct($distinct);
        }

        return $this;
    }

    public function whereEq(string $field, mixed $value): static
    {
        foreach ($this->queries as $query) {
            $query->whereEq($field, $value);
        }

        return $this;
    }

    public function whereCmp(string $field, string $operator, mixed $value): static
    {
        foreach ($this->queries as $query) {
            $query->whereCmp($field, $operator, $value);
        }

        return $this;
    }

    public function whereMod(string $field, int $divisor, int $remainder): static
    {
        foreach ($this->queries as $query) {
            $query->whereMod($field, $divisor, $remainder);
        }

        return $this;
    }

    public function whereExpr(string $expr, ?array $bind = null): static
    {
        foreach ($this->queries as $query) {
            $query->whereExpr($expr, $bind);
        }

        return $this;
    }

    public function whereBetween(string $field, mixed $min, mixed $max): static
    {
        foreach ($this->queries as $query) {
            $query->whereBetween($field, $min, $max);
        }

        return $this;
    }

    public function whereNotBetween(string $field, mixed $min, mixed $max): static
    {
        foreach ($this->queries as $query) {
            $query->whereNotBetween($field, $min, $max);
        }

        return $this;
    }

    public function whereIn(string $field, array $values): static
    {
        foreach ($this->queries as $query) {
            $query->whereIn($field, $values);
        }

        return $this;
    }

    public function whereNotIn(string $field, array $values): static
    {
        foreach ($this->queries as $query) {
            $query->whereNotIn($field, $values);
        }

        return $this;
    }

    public function whereInset(string $field, string $value): static
    {
        foreach ($this->queries as $query) {
            $query->whereInset($field, $value);
        }

        return $this;
    }

    public function whereNotInset(string $field, string $value): static
    {
        foreach ($this->queries as $query) {
            $query->whereNotInset($field, $value);
        }

        return $this;
    }

    public function whereContains(string|array $fields, string $value): static
    {
        foreach ($this->queries as $query) {
            $query->whereContains($fields, $value);
        }

        return $this;
    }

    public function whereNotContains(string|array $fields, string $value): static
    {
        foreach ($this->queries as $query) {
            $query->whereNotContains($fields, $value);
        }

        return $this;
    }

    public function whereStartsWith(string|array $fields, string $value, ?int $length = null): static
    {
        foreach ($this->queries as $query) {
            $query->whereStartsWith($fields, $value, $length);
        }

        return $this;
    }

    public function whereNotStartsWith(string|array $fields, string $value, ?int $length = null): static
    {
        foreach ($this->queries as $query) {
            $query->whereNotStartsWith($fields, $value, $length);
        }

        return $this;
    }

    public function whereEndsWith(string|array $fields, string $value): static
    {
        foreach ($this->queries as $query) {
            $query->whereEndsWith($fields, $value);
        }

        return $this;
    }

    public function whereNotEndsWith(string|array $fields, string $value): static
    {
        foreach ($this->queries as $query) {
            $query->whereNotEndsWith($fields, $value);
        }

        return $this;
    }

    public function whereLike(string|array $fields, string $value): static
    {
        foreach ($this->queries as $query) {
            $query->whereLike($fields, $value);
        }

        return $this;
    }

    public function whereNotLike(string|array $fields, string $value): static
    {
        foreach ($this->queries as $query) {
            $query->whereNotLike($fields, $value);
        }

        return $this;
    }

    public function whereRegex(string $field, string $regex, string $flags = ''): static
    {
        foreach ($this->queries as $query) {
            $query->whereRegex($field, $regex, $flags);
        }

        return $this;
    }

    public function whereNotRegex(string $field, string $regex, string $flags = ''): static
    {
        foreach ($this->queries as $query) {
            $query->whereNotRegex($field, $regex, $flags);
        }

        return $this;
    }

    public function whereNull(string $field): static
    {
        foreach ($this->queries as $query) {
            $query->whereNull($field);
        }

        return $this;
    }

    public function whereNotNull(string $field): static
    {
        foreach ($this->queries as $query) {
            $query->whereNotNull($field);
        }

        return $this;
    }

    public function where1v1(string $id, string $value): static
    {
        foreach ($this->queries as $query) {
            $query->where1v1($id, $value);
        }

        return $this;
    }

    public function whereRaw(string $filter, ?array $bind = null): static
    {
        foreach ($this->queries as $query) {
            $query->whereRaw($filter, $bind);
        }

        return $this;
    }

    public function orderBy(string|array $orderBy): static
    {
        parent::orderBy($orderBy);

        foreach ($this->queries as $query) {
            $query->orderBy($this->order);
        }

        return $this;
    }

    public function forceUseMaster(bool $forceUseMaster = true): static
    {
        foreach ($this->queries as $query) {
            $query->forceUseMaster($forceUseMaster);
        }

        return $this;
    }

    public function having(string|array $having, array $bind = []): static
    {
        foreach ($this->queries as $query) {
            $query->having($having, $bind);
        }

        return $this;
    }

    public function execute(): array
    {
        $result = [];

        if ($this->order) {
            if ($this->limit) {
                foreach ($this->queries as $query) {
                    $query->limit($this->offset + $this->limit, 0);
                }
            }

            $valid_times = 0;
            foreach ($this->queries as $query) {
                if ($r = $query->execute()) {
                    $valid_times++;
                    $result = $result ? array_merge($result, $r) : $r;
                }
            }

            if ($valid_times > 1) {
                $result = Arr::sort($result, $this->order);
            }

            $result = $this->limit ? array_slice($result, $this->offset ?? 0, $this->limit) : $result;
        } elseif ($this->limit) {
            foreach ($this->queries as $query) {
                if ($r = $query->execute()) {
                    $result = $result ? array_merge($result, $r) : $r;
                    if (count($result) >= $this->offset + $this->limit) {
                        $result = array_slice($result, $this->offset ?? 0, $this->limit);
                        return $this->index ? Arr::indexby($result, $this->index) : $result;
                    }
                }
            }

            $result = $result ? array_slice($result, $this->offset ?? 0, $this->limit) : [];
        } else {
            foreach ($this->queries as $query) {
                if ($r = $query->execute()) {
                    $result = $result ? array_merge($result, $r) : $r;
                }
            }
        }

        return $this->index ? Arr::indexby($result, $this->index) : $result;
    }

    public function aggregate(array $expr): array
    {
        throw new NotSupportedException(__METHOD__);
    }

    public function values(string $field): array
    {
        $values = [];
        $valid_times = 0;
        foreach ($this->queries as $query) {
            if ($t = $query->values($field)) {
                $valid_times++;
                $values = $values ? array_merge($values, $t) : $t;
            }
        }

        if ($valid_times > 1) {
            $values = array_values(array_unique($values));
            if ($this->order) {
                if (current($this->order) === SORT_ASC) {
                    sort($values);
                } else {
                    rsort($values);
                }
            }
        }

        return $values;
    }

    /**
     * @param string $field
     *
     * @return int
     */
    public function count(string $field = '*'): int
    {
        $r = 0;
        foreach ($this->queries as $query) {
            $t = $query->count($field);
            $r += $t;
        }

        return $r;
    }

    public function exists(): bool
    {
        foreach ($this->queries as $query) {
            if ($query->exists()) {
                return true;
            }
        }

        return false;
    }

    public function update(array $fieldValues): int
    {
        $r = 0;
        foreach ($this->queries as $query) {
            $r += $query->update($fieldValues);
        }

        return $r;
    }

    public function delete(): int
    {
        $r = 0;
        foreach ($this->queries as $query) {
            $r += $query->delete();
        }

        return $r;
    }

    public function join(string $table, ?string $condition = null, ?string $alias = null, ?string $type = null): static
    {
        foreach ($this->queries as $query) {
            $query->join($table, $condition, $alias, $type);
        }

        return $this;
    }

    public function getSql(): string
    {
        throw new NotSupportedException(__METHOD__);
    }
}