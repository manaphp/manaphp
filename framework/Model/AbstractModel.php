<?php
declare(strict_types=1);

namespace ManaPHP\Model;

use AllowDynamicProperties;
use ArrayAccess;
use JsonSerializable;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Exception\UnknownPropertyException;
use ManaPHP\Helper\Container;
use ManaPHP\Persistence\RestrictionsInterface;
use ManaPHP\Query\QueryInterface;
use ManaPHP\Validating\ConstraintInterface;
use ManaPHP\Validating\ValidatorInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;
use Stringable;
use function in_array;
use function is_array;
use function is_int;
use function is_object;
use function is_string;

#[AllowDynamicProperties]
abstract class AbstractModel implements ModelInterface, ArrayAccess, JsonSerializable, Stringable
{
    protected ?array $_snapshot = [];

    public function __construct(array $data = [])
    {
        if ($data) {
            $this->_snapshot = $data;

            foreach ($data as $field => $value) {
                $this->{$field} = $value;
            }
        }
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param array|RestrictionsInterface $filters =model_var(new static)
     * @param array                       $fields  =model_fields(new static)
     * @param array                       $orders
     *
     * @return  static[]
     */
    public static function all(array|RestrictionsInterface $filters = [], array $fields = [], array $orders = []): array
    {
        return static::select($fields)->where($filters)->orderBy($orders)->fetch();
    }

    /**
     * @param array $fields  =model_fields(new static)
     * @param array $filters =model_var(new static)
     *
     * @return array
     */
    public static function lists(array $fields, array $filters = []): array
    {
        $keyField = Container::get(ModelsInterface::class)->getPrimaryKey(static::class);
        if (!in_array($keyField, $fields, true)) {
            array_unshift($fields, $keyField);
        }

        if (property_exists(static::class, 'display_order')) {
            $order = ['display_order' => SORT_DESC, $keyField => SORT_ASC];
        } else {
            $order = [$keyField => SORT_ASC];
        }
        return static::select($fields)->where($filters)->orderBy($order)->execute();
    }

    /**
     * @param array $fields =model_fields(new static)
     *
     * @return QueryInterface <static>
     */
    public static function select(array $fields = []): QueryInterface
    {
        return static::query()->select($fields);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param array $filters =model_var(new static)
     * @param array $fields  =model_fields(new static)
     *
     * @return static|null
     */
    public static function first(array $filters, array $fields = []): ?static
    {
        $rs = static::select($fields)->where($filters)->limit(1)->fetch();
        return $rs[0] ?? null;
    }

    /**
     * @param array $filters =model_var(new static)
     *
     * @return static
     */
    public static function firstOrNew(array $filters): static
    {
        if (($instance = static::first($filters)) === null) {
            $instance = new static();
            foreach ($filters as $k => $v) {
                $instance->$k = $v;
            }
        }

        return $instance;
    }

    /**
     * Allows to query the last record that match the specified conditions
     *
     * @param array $filters =model_var(new static)
     * @param array $fields  =model_fields(new static)
     *
     * @return static|null
     */
    public static function last(array $filters = [], array $fields = []): ?static
    {
        $primaryKey = Container::get(ModelsInterface::class)->getPrimaryKey(static::class);
        $rs = static::select($fields)->where($filters)->orderBy([$primaryKey => SORT_DESC])->limit(1)->fetch();
        return $rs[0] ?? null;
    }

    /**
     * @param array  $filters =model_var(new static)
     * @param string $field   =model_field(new static)
     *
     * @return int|float|string|null
     */
    public static function value(array $filters, string $field): mixed
    {
        $rs = static::select([$field])->where($filters)->limit(1)->execute();
        return $rs ? $rs[0][$field] : null;
    }

    /**
     * @param string $field   =model_field(new static)
     * @param array  $filters =model_var(new static)
     *
     * @return array
     */
    public static function values(string $field, array $filters = []): array
    {
        return static::where($filters)->orderBy([$field => SORT_ASC])->values($field);
    }

    /**
     * @param string|array $kv      =model_fields(new static) ?? model_field(new static)
     * @param array        $filters =model_var(new static)
     *
     * @return array
     */
    public static function kvalues(string|array $kv, array $filters = []): array
    {
        $dict = [];

        if (is_string($kv)) {
            $key = Container::get(ModelsInterface::class)->getPrimaryKey(static::class);
            $value = $kv;
            foreach (static::select([$key, $value])->where($filters)->execute() as $row) {
                $dict[$row[$key]] = $row[$value];
            }
        } else {
            $key = array_key_first($kv);
            $fields = $kv[$key];

            if (is_string($fields)) {
                $value = $fields;
                foreach (static::select([$key, $value])->where($filters)->execute() as $row) {
                    $dict[$row[$key]] = $row[$value];
                }
            } else {
                array_unshift($fields, $key);
                foreach (static::select($fields)->where($filters)->execute() as $row) {
                    $dict[$row[$key]] = $row;
                }
            }
        }

        return $dict;
    }

    /**
     * @param array $filters =model_var(new static)
     *
     * @return bool
     */
    public static function exists(array $filters): bool
    {
        return static::where($filters)->exists();
    }

    /**
     * Assigns values to a model from an array
     *
     * @param array|object $data   =model_var(new static)
     * @param array        $fields =model_fields(static::class)
     *
     * @return static
     */
    public function assign(array|object $data, array $fields): static
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
     * @param array $fields =model_fields(new static)
     *
     * @return void
     */
    public function validate(array $fields): void
    {
        $validator = Container::get(ValidatorInterface::class);

        $validation = $validator->beginValidate($this);
        foreach ($fields as $field) {
            $rProperty = new ReflectionProperty(static::class, $field);
            $attributes = $rProperty->getAttributes(ConstraintInterface::class, ReflectionAttribute::IS_INSTANCEOF);
            if ($attributes !== []) {
                $validation->field = $field;
                $validation->value = $this->$field ?? null;

                foreach ($attributes as $attribute) {
                    /** @var ConstraintInterface $constraint */
                    $constraint = $attribute->newInstance();
                    if (!$validation->validate($constraint)) {
                        break;
                    }
                }

                if (!$validation->hasError($field)) {
                    $this->$field = $validation->value;
                }
            }
        }
        $validator->endValidate($validation);
    }

    protected function autoFillCreated(): void
    {
        $autoFiller = Container::get(AutoFillerInterface::class);
        $autoFiller->fillCreated($this);
    }

    protected function autoFillUpdated(): void
    {
        $autoFiller = Container::get(AutoFillerInterface::class);
        $autoFiller->fillUpdated($this);
    }

    /**
     * @param array $fieldValues =model_var(new static)
     * @param array $filters     =model_var(new static)
     *
     * @return int
     */
    public static function updateAll(array $fieldValues, array $filters): int
    {
        return static::where($filters)->update($fieldValues);
    }

    /**
     * @param array $filters =model_var(new static)
     *
     * @return int
     */
    public static function deleteAll(array $filters): int
    {
        return static::where($filters)->delete();
    }

    public function with(array $withs): static
    {
        $relations = Container::get(RelationsInterface::class);

        $relations->earlyLoad(static::class, [$this], $withs);
        return $this;
    }

    /**
     * Returns the instance as an array representation
     *
     * @return array =model_var(new static)
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

    /**
     * @param array $fields =model_fields(new static)
     *
     * @return static
     */
    public function only(array $fields): static
    {
        $model = new static();
        $model->_snapshot = null;

        foreach ($fields as $field) {
            $model->$field = $this->$field;
        }

        return $model;
    }

    /**
     * @param array $fields =model_fields(new static)
     *
     * @return static
     */
    public function except(array $fields): static
    {
        $model = clone $this;
        $model->_snapshot = null;

        foreach ($fields as $field) {
            unset($model->$field);
        }

        return $model;
    }

    /**
     * Returns the internal snapshot data
     *
     * @return array =model_var(new static)
     */
    public function getSnapshotData(): array
    {
        return $this->_snapshot;
    }

    /**
     * Returns a list of changed values
     *
     * @return array =model_fields(new static)
     */
    public function getChangedFields(): array
    {
        $snapshot = $this->_snapshot;

        $changed = [];
        foreach (Container::get(ModelsInterface::class)->getFields(static::class) as $field) {
            if (isset($snapshot[$field])) {
                if ($this->{$field} !== $snapshot[$field]) {
                    $changed[] = $field;
                }
            } elseif (isset($this->$field)) {
                $changed[] = $field;
            }
        }

        return $changed;
    }

    /**
     * Check if a specific attribute has changed
     * This only works if the model is keeping data snapshots
     *
     * @param array $fields =model_fields(new static)
     *
     * @return bool
     */
    public function hasChanged(array $fields): bool
    {
        $snapshot = $this->_snapshot;

        foreach ($fields as $field) {
            if (!isset($snapshot[$field]) || $this->{$field} !== $snapshot[$field]) {
                return true;
            }
        }

        return false;
    }

    public function fireEvent(object $event): void
    {
        Container::get(EventDispatcherInterface::class)->dispatch($event);
    }

    public function relations(): array
    {
        return [];
    }

    /**
     * @param ?string $alias
     *
     * @return QueryInterface <static>
     */
    public static function query(?string $alias = null): QueryInterface
    {
        $query = static::newQuery();

        return $alias ? $query->from(static::class, $alias) : $query;
    }

    /**
     * @param array $filters =model_var(new static)
     *
     * @return QueryInterface <static>
     */
    public static function where(array $filters): QueryInterface
    {
        return static::select()->where($filters);
    }

    /**
     * @param string $name
     *
     * @return ModelInterface|ModelInterface[]|mixed
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
                throw new NotSupportedException(['`{1}` model does not define `{2}` relation', static::class, $relation]
                );
            }
        }
        throw new NotSupportedException(['`{1}` does not contain `{2}` method', static::class, $name]);
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        $data = [];

        foreach (get_object_vars($this) as $field => $value) {
            if ($field === '_snapshot') {
                continue;
            }

            if (is_object($value) && !$value instanceof ModelInterface) {
                continue;
            }

            $data[$field] = $value;
        }

        if ($changedFields = $this->getChangedFields()) {
            $data['*changed_fields*'] = $changedFields;
        }

        foreach (Container::get(ModelsInterface::class)->getFields(static::class) as $field) {
            if (!isset($this->$field)) {
                continue;
            }

            $value = $this->$field;

            /**1973/3/3 17:46:40*/
            if (is_int($value) && $value > 100000000
                && !str_ends_with($field, '_id')
                && !str_ends_with($field, 'Id')
            ) {
                $data['*human_time*'][$field] = date('Y-m-d H:i:s', $value);
            }

            if (is_numeric($value)) {
                foreach ((new ReflectionClass(static::class))->getConstants() as $cName => $cValue) {
                    /** @noinspection TypeUnsafeComparisonInspection */
                    if ($cValue == $value && stripos($cName, $field) === 0) {
                        $data['*human_const*'][$field] = $cName;
                    }
                }
            }
        }

        return $data;
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