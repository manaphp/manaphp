<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

use ArrayAccess;
use JsonSerializable;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Exception\UnknownPropertyException;
use ManaPHP\Helper\Container;
use ManaPHP\Persistence\Event\EntityEventInterface;
use Stringable;
use function get_object_vars;
use function is_array;
use function is_object;

class Entity implements ArrayAccess, JsonSerializable, Stringable
{
    public function __construct(array $data = [])
    {
        if ($data) {
            foreach ($data as $field => $value) {
                $this->{$field} = $value;
            }
        }
    }

    /**
     * Assigns values to an entity from an array
     *
     * @param array|Entity $data   =entity_var(new static)
     * @param array        $fields =entity_fields(static::class)
     *
     * @return static
     */
    public function assign(array|Entity $data, array $fields): static
    {
        if (is_object($data)) {
            foreach ($fields as $field) {
                $this->$field = $data->$field;
            }
        } else {
            foreach ($fields as $field) {
                $this->$field = $data[$field];
            }
        }

        return $this;
    }

    /**
     * Returns the instance as an array representation
     *
     * @return array =entity_var(new static)
     */
    public function toArray(): array
    {
        $data = [];

        foreach (get_object_vars($this) as $field => $value) {
            if ($value === null || $field[0] === '_') {
                continue;
            }

            if (is_object($value)) {
                if ($value instanceof self) {
                    $value = $value->toArray();
                } else {
                    continue;
                }
            } elseif (is_array($value) && ($first = current($value)) && $first instanceof self) {
                foreach ($value as $k => $v) {
                    $value[$k] = $v->toArray();
                }
            }

            $data[$field] = $value;
        }

        return $data;
    }

    public function onEvent(EntityEventInterface $entityEvent)
    {
    }

    /**
     * @param string $name
     *
     * @return Entity|Entity[]|mixed
     * @throws UnknownPropertyException
     */
    public function __get(mixed $name): mixed
    {
        $method = 'get' . ucfirst($name);
        if (method_exists($this, $method)) {
            return $this->$name = $this->$method()->fetch();
        } elseif (Container::has($name)) {
            return $this->{$name} = Container::get($name);
        } elseif (($relations = Container::get(RelationsInterface::class))->has(static::class, $name)) {
            return $this->$name = $relations->lazyLoad($this, $name)->fetch();
        } else {
            throw new UnknownPropertyException(['`{1}` does not contain `{2}` field.`', static::class, $name]);
        }
    }

    public function __set(mixed $name, mixed $value): void
    {
        $this->$name = $value;
    }

    public function __isset(mixed $name): bool
    {
        return isset($this->$name);
    }

    public function __call(string $name, array $arguments): mixed
    {
        if (str_starts_with($name, 'get')) {
            $relations = Container::get(RelationsInterface::class);

            $relation = lcfirst(substr($name, 3));
            if ($relations->has(static::class, $relation)) {
                return $relations->lazyLoad($this, $relation);
            } else {
                throw new NotSupportedException(
                    ['`{1}` entity does not define `{2}` relation', static::class, $relation]
                );
            }
        }
        throw new NotSupportedException(['`{1}` does not contain `{2}` method', static::class, $name]);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->$offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->$offset;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->$offset = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->$offset = null;
    }

    public function jsonSerialize(): array
    {
        $data = $this->toArray();

        return $this instanceof SerializeNormalizable ? $this->serializeNormalize($data) : $data;
    }

    public function __toString(): string
    {
        return json_stringify($this->toArray());
    }
}