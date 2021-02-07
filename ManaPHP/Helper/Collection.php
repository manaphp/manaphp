<?php

namespace ManaPHP\Helper;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;

class Collection implements JsonSerializable, Countable, IteratorAggregate, ArrayAccess
{
    /**
     * @var array
     */
    protected $items;

    /**
     * @param array $items
     */
    public function __construct($items)
    {
        $this->items = $items;
    }

    /**
     * @param callable $callback
     *
     * @return static
     */
    public function filter($callback)
    {
        $items = [];
        foreach ($this->items as $key => $value) {
            if ($callback($value, $key) === true) {
                $items[$key] = $value;
            }
        }

        return new static($items);
    }

    /**
     * @return static
     */
    public function flip()
    {
        return new static(array_flip($this->items));
    }

    /**
     * @param array $items
     *
     * @return static
     */
    public function merge($items)
    {
        return new static(array_merge($this->items, $items));
    }

    /**
     * @param callable $callback
     *
     * @return static
     */
    public function map($callback)
    {
        $items = [];
        foreach ($this->items as $key => $value) {
            $items[$key] = $callback($value, $key);
        }

        return new static($items);
    }

    /**
     * @param callable $callback
     *
     * @return static
     */
    public function transform($callback)
    {
        foreach ($this->items as $key => $value) {
            $this->items[$key] = $callback($value, $key);
        }

        return $this;
    }

    /**
     * @param callable $callback
     * @param mixed    $initial
     *
     * @return mixed
     */
    public function reduce($callback, $initial = null)
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * @param array $items
     *
     * @return static
     */
    public function diff($items)
    {
        return new static(array_diff($this->items, $items));
    }

    /**
     * @param array $items
     *
     * @return static
     */
    public function diff_key($items)
    {
        return new static(array_diff_key($this->items, $items));
    }

    /**
     * @param array    $items
     * @param callable $callback
     *
     * @return static
     */
    public function udiff($items, $callback)
    {
        return new static(array_udiff($this->items, $items, $callback));
    }

    /**
     * @param bool $preserve_keys
     *
     * @return static
     */
    public function reverse($preserve_keys = false)
    {
        return new static(array_reverse($this->items, $preserve_keys));
    }

    /**
     * @param int $sort_flags
     *
     * @return static
     */
    public function sort($sort_flags = SORT_REGULAR)
    {
        $items = $this->items;
        sort($items, $sort_flags);
        return new static($items);
    }

    /**
     * @param int $sort_flags
     *
     * @return static
     */
    public function rsort($sort_flags = SORT_REGULAR)
    {
        $items = $this->items;
        rsort($items, $sort_flags);
        return new static($items);
    }

    /**
     * @param int $sort_flags
     *
     * @return static
     */
    public function asort($sort_flags = SORT_REGULAR)
    {
        $items = $this->items;
        asort($items, $sort_flags);
        return new static($items);
    }

    /**
     * @param int $sort_flags
     *
     * @return static
     */
    public function arsort($sort_flags = SORT_REGULAR)
    {
        $items = $this->items;
        arsort($items, $sort_flags);
        return new static($items);
    }

    /**
     * @param callable $callback
     *
     * @return static
     */
    public function usort($callback)
    {
        $items = $this->items;
        usort($items, $callback);
        return new static($items);
    }

    /**
     * @param callable $callback
     *
     * @return static
     */
    public function uasort($callback)
    {
        $items = $this->items;
        uasort($items, $callback);
        return new static($items);
    }

    /**
     * @return static
     */
    public function shuffle()
    {
        $items = $this->items;
        shuffle($items);
        return new static($items);
    }

    /**
     * @param int  $offset
     * @param int  $length
     * @param bool $preserve_keys
     *
     * @return static
     */
    public function slice($offset, $length = null, $preserve_keys = false)
    {
        return new static(array_slice($this->items, $offset, $length, $preserve_keys));
    }

    /**
     * @param int  $count
     * @param bool $preserve_keys
     *
     * @return static
     */
    public function skip($count, $preserve_keys = false)
    {
        return new static(array_slice($this->items, $count, null, $preserve_keys));
    }

    /**
     * @param int  $size
     * @param bool $preserve_keys
     *
     * @return static
     */
    public function chunk($size, $preserve_keys = false)
    {
        $chunks = [];

        foreach (array_chunk($this->items, $size, $preserve_keys) as $chunk) {
            $chunks[] = new static($chunk);
        }

        return new static($chunks);
    }

    /**
     * @return array
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * @return static
     */
    public function keys()
    {
        return new static(array_keys($this->items));
    }

    /**
     * @return static
     */
    public function values()
    {
        return new static(array_values($this->items));
    }

    /**
     * @param array $sorts
     *
     * @return static
     */
    public function sortBy($sorts)
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

    public function jsonSerialize()
    {
        return $this->items;
    }

    public function count()
    {
        return count($this->items);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    public function offsetExists($offset)
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->items[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }
}