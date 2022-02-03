<?php
declare(strict_types=1);

namespace ManaPHP\Helper;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;

class Collection implements JsonSerializable, Countable, IteratorAggregate, ArrayAccess
{
    protected array $items;

    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function filter(callable $callback): static
    {
        $items = [];
        foreach ($this->items as $key => $value) {
            if ($callback($value, $key) === true) {
                $items[$key] = $value;
            }
        }

        return new static($items);
    }

    public function flip(): static
    {
        return new static(array_flip($this->items));
    }

    public function merge(array $items): static
    {
        return new static(array_merge($this->items, $items));
    }

    public function map(callable $callback): static
    {
        $items = [];
        foreach ($this->items as $key => $value) {
            $items[$key] = $callback($value, $key);
        }

        return new static($items);
    }

    public function transform(callable $callback): static
    {
        foreach ($this->items as $key => $value) {
            $this->items[$key] = $callback($value, $key);
        }

        return $this;
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    public function diff(array $items): static
    {
        return new static(array_diff($this->items, $items));
    }

    public function diff_key(array $items): static
    {
        return new static(array_diff_key($this->items, $items));
    }

    public function udiff(array $items, callable $callback): static
    {
        return new static(array_udiff($this->items, $items, $callback));
    }

    public function reverse(bool $preserve_keys = false): static
    {
        return new static(array_reverse($this->items, $preserve_keys));
    }

    public function sort(int $sort_flags = SORT_REGULAR): static
    {
        $items = $this->items;
        sort($items, $sort_flags);
        return new static($items);
    }

    public function rsort(int $sort_flags = SORT_REGULAR): static
    {
        $items = $this->items;
        rsort($items, $sort_flags);
        return new static($items);
    }

    public function asort(int $sort_flags = SORT_REGULAR): static
    {
        $items = $this->items;
        asort($items, $sort_flags);
        return new static($items);
    }

    public function arsort(int $sort_flags = SORT_REGULAR): static
    {
        $items = $this->items;
        arsort($items, $sort_flags);
        return new static($items);
    }

    public function usort(callable $callback): static
    {
        $items = $this->items;
        usort($items, $callback);
        return new static($items);
    }

    public function uasort(callable $callback): static
    {
        $items = $this->items;
        uasort($items, $callback);
        return new static($items);
    }

    public function shuffle(): static
    {
        $items = $this->items;
        shuffle($items);
        return new static($items);
    }

    public function slice(int $offset, ?int $length = null, bool $preserve_keys = false): static
    {
        return new static(array_slice($this->items, $offset, $length, $preserve_keys));
    }

    public function skip(int $count, bool $preserve_keys = false): static
    {
        return new static(array_slice($this->items, $count, null, $preserve_keys));
    }

    public function chunk(int $size, bool $preserve_keys = false): static
    {
        $chunks = [];

        foreach (array_chunk($this->items, $size, $preserve_keys) as $chunk) {
            $chunks[] = new static($chunk);
        }

        return new static($chunks);
    }

    public function all(): array
    {
        return $this->items;
    }

    public function keys(): static
    {
        return new static(array_keys($this->items));
    }

    public function values(): static
    {
        return new static(array_values($this->items));
    }

    public function sortBy(array $sorts): static
    {
        $normalized_sorts = [];
        foreach ($sorts as $key => $value) {
            if (is_int($key)) {
                $normalized_sorts[$value] = SORT_ASC;
            } else {
                if ($value === SORT_ASC || $value === SORT_DESC) {
                    $normalized_sorts[$key] = $value;
                } elseif ($value === 'ASC' || $value === 'asc') {
                    $normalized_sorts[$key] = SORT_ASC;
                } else {
                    $normalized_sorts[$key] = SORT_DESC;
                }
            }
        }

        $items = $this->items;
        usort(
            $items, static function ($left, $right) use ($normalized_sorts) {
            foreach ($normalized_sorts as $field => $sort) {
                $left_value = $left[$field];
                $right_value = $right[$field];

                $cmp = is_string($left_value) ? strcmp($left_value, $right_value) : $left_value - $right_value;
                if ($cmp > 0) {
                    return $sort === SORT_ASC ? 1 : -1;
                } elseif ($cmp < 0) {
                    return $sort === SORT_ASC ? -1 : 1;
                }
            }

            return 0;
        }
        );

        return new static($items);
    }

    public function jsonSerialize(): array
    {
        return $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value)
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset)
    {
        unset($this->items[$offset]);
    }
}