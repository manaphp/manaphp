<?php
declare(strict_types=1);

namespace ManaPHP\Model;

use ArrayAccess;
use JsonSerializable;
use ManaPHP\Db\SqlFragmentable;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Exception\UnknownPropertyException;
use ManaPHP\Helper\Container;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Identifying\IdentityInterface;
use ManaPHP\Query\Paginator;
use ManaPHP\Query\QueryInterface;
use ManaPHP\Validating\Validator\ValidateFailedException;
use ManaPHP\Validating\ValidatorInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionClass;

abstract class AbstractModel implements ModelInterface, ArrayAccess, JsonSerializable
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
     * @return array =model_var(new static) ?: [$field => \PHPSTORM_META\validator_rule()]
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param array  $filters =model_var(new static)
     * @param ?array $fields  =model_fields(new static)
     *
     * @return  static[]
     */
    public static function all(array $filters = [], ?array $fields = null): array
    {
        return static::select($fields)->where($filters)->fetch();
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param array  $filters =model_var(new static)
     * @param ?array $fields  =model_fields(new static)
     *
     * @return  \ManaPHP\Query\Paginator
     */
    public static function paginate(array $filters = [], ?array $fields = null): Paginator
    {
        return static::select($fields)->search($filters)->paginate();
    }

    /**
     * @param string|array $fields  =model_fields(new static) ?? model_field(new static)
     * @param array        $filters =model_var(new static)
     *
     * @return array
     */
    public static function lists(string|array $fields, array $filters = []): array
    {
        if (is_string($fields)) {
            $fields = [$fields];
        }

        $keyField = Container::get(ModelManagerInterface::class)->getPrimaryKey(static::class);
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
     * @param int|string $id
     * @param ?array     $fields =model_fields(new static)
     *
     * @return static
     */
    public static function get(int|string $id, ?array $fields = null): static
    {
        $primaryKey = Container::get(ModelManagerInterface::class)->getPrimaryKey(static::class);

        return static::firstOrFail([$primaryKey => $id], $fields);
    }

    /**
     * @param ?array  $fields =model_fields(new static)
     * @param ?string $alias
     *
     * @return \ManaPHP\Query\QueryInterface <static>
     */
    public static function select(?array $fields = null, ?string $alias = null): QueryInterface
    {
        return static::query($alias)->select($fields ?? []);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param array  $filters =model_var(new static)
     * @param ?array $fields  =model_fields(new static)
     *
     * @return static|null
     */
    public static function first(array $filters, ?array $fields = null): ?static
    {
        $rs = static::select($fields ?? [])->where($filters)->limit(1)->fetch();
        return $rs[0] ?? null;
    }

    /**
     * @param array  $filters =model_var(new static)
     * @param ?array $fields  =model_fields(new static)
     *
     * @return static
     */
    public static function firstOrFail(array $filters, ?array $fields = null): static
    {
        $r = static::first($filters, $fields);
        if ($r === null) {
            throw new NotFoundException(static::class, $filters);
        }

        return $r;
    }

    /**
     * @param array $filters =model_var(new static)
     * @param array $fields  =model_fields(new static)
     *
     * @return static
     */
    public static function firstOrNew(array $filters, array $fields = []): static
    {
        if (($instance = static::first($filters)) === null) {
            $instance = new static();
            foreach ($filters as $k => $v) {
                $instance->$k = $v;
            }
        }

        return $instance->load($fields);
    }

    /**
     * @param array $filters =model_var(new static)
     * @param array $fields  =model_fields(new static)
     *
     * @return static
     */
    public static function firstOrCreate(array $filters, array $fields = []): static
    {
        return static::firstOrNew($filters, $fields)->save();
    }

    /**
     * Allows to query the last record that match the specified conditions
     *
     * @param array  $filters =model_var(new static)
     * @param ?array $fields  =model_fields(new static)
     *
     * @return static|null
     */
    public static function last(array $filters = [], ?array $fields = null): ?static
    {
        $primaryKey = Container::get(ModelManagerInterface::class)->getPrimaryKey(static::class);
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
     * @param array  $filters =model_var(new static)
     * @param string $field   =model_field(new static)
     *
     * @return int|float|string
     */
    public static function valueOrFail(array $filters, string $field): mixed
    {
        $value = static::value($filters, $field);
        if ($value === null) {
            throw new NotFoundException(static::class, $filters);
        } else {
            return $value;
        }
    }

    /**
     * @param array  $filters =model_var(new static)
     * @param string $field   =model_field(new static)
     * @param mixed  $default
     *
     * @return float|int|string
     */
    public static function valueOrDefault(array $filters, mixed $field, mixed $default): mixed
    {
        return ($value = static::value($filters, $field)) === null ? $default : $value;
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
            $key = Container::get(ModelManagerInterface::class)->getPrimaryKey(static::class);
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
     * @param array  $filters =model_var(new static)
     * @param string $field   =model_field(new static)
     *
     * @return int
     */
    public static function count(array $filters = [], string $field = '*'): int
    {
        return static::where($filters)->count($field);
    }

    /**
     * Allows to calculate a summary on a field that match the specified conditions
     *
     * @param string $field   =model_field(new static)
     * @param array  $filters =model_var(new static)
     *
     * @return int|float|null
     */
    public static function sum(string $field, array $filters = []): mixed
    {
        return static::where($filters)->sum($field);
    }

    /**
     * Allows to get the max value of a column that match the specified conditions
     *
     * @param string $field   =model_field(new static)
     * @param array  $filters =model_var(new static)
     *
     * @return int|float|null
     */
    public static function max(string $field, array $filters = []): mixed
    {
        return static::where($filters)->max($field);
    }

    /**
     * Allows to get the min value of a column that match the specified conditions
     *
     *
     * @param string $field   =model_field(new static)
     * @param array  $filters =model_var(new static)
     *
     * @return int|float|null
     */
    public static function min(string $field, array $filters = []): mixed
    {
        return static::where($filters)->min($field);
    }

    /**
     * Allows to calculate the average value on a column matching the specified conditions
     *
     * @param string $field   =model_field(new static)
     * @param array  $filters =model_var(new static)
     *
     * @return float|null
     */
    public static function avg(string $field, array $filters = []): ?float
    {
        return (float)static::where($filters)->avg($field);
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
     * @param ?array $fields =model_fields(new static)
     *
     * @return static
     */
    public function load(?array $fields = null): static
    {
        $fields = $fields ?? Container::get(ModelManagerInterface::class)->getFillable(static::class);

        $request = Container::get(RequestInterface::class);

        $data = $request->all();

        foreach ($fields as $k => $v) {
            if (is_string($k)) {
                $field = $k;
                $value = $v;
            } elseif (isset($data[$field = $v])) {
                $value = $data[$field];
            } else {
                continue;
            }

            $this->$field = is_string($value) ? trim($value) : $value;
        }

        return $this;
    }

    public function fill(array $kv): static
    {
        return $this->assign($kv, Container::get(ModelManagerInterface::class)->getFillable(static::class));
    }

    /**
     * @param ?array $fields =model_fields(new static)
     *
     * @return void
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function validate(?array $fields = null): void
    {
        if (!$rules = $this->rules()) {
            return;
        }

        if (isset($rules[0])) {
            throw new MisuseException(['`{1}` rules must be an associative array', static::class]);
        }

        $validator = Container::get(ValidatorInterface::class);

        $errors = [];

        foreach ($fields ?: $this->getChangedFields() as $field) {
            if (!isset($rules[$field]) || $this->$field instanceof SqlFragmentable) {
                continue;
            }

            try {
                $this->$field = $validator->validateModel($field, $this, $rules[$field]);
            } catch (ValidateFailedException $exception) {
                $errors += $exception->getErrors();
            }
        }

        if ($errors) {
            throw new ValidateFailedException($errors);
        }
    }

    /**
     * @param string $field =model_field(new static)
     * @param ?array $rules
     *
     * @return void
     */
    public function validateField(string $field, array $rules = null): void
    {
        if ($rules === null) {
            if (!isset($rules[$field])) {
                return;
            }

            $rules = $rules[$field];
        }

        $validator = Container::get(ValidatorInterface::class);

        $this->$field = $validator->validateModel($field, $this, $rules);
    }

    public function getAutoCreatedData(): array
    {
        $modelManager = Container::get(ModelManagerInterface::class);
        $current_time = time();
        $dateFormat = $modelManager->getDateFormat(static::class);

        $identity = Container::get(IdentityInterface::class);
        $user_id = $identity->getId(0);
        $user_name = $identity->getName('');

        $data = [];
        foreach ($modelManager->getFields(static::class) as $field) {
            if ($this->$field !== null) {
                continue;
            }

            $needle = ",$field,";
            if (str_contains(',created_time,createdTime,created_at,createdAt,', $needle)) {
                $data[$field] = date($dateFormat, $current_time);
            } elseif (str_contains(',updated_time,updatedTime,updated_at,updatedAt,', $needle)) {
                $data[$field] = date($dateFormat, $current_time);
            } elseif (str_contains(',creator_id,creatorId,created_id,createdId,', $needle)) {
                $data[$field] = $user_id;
            } elseif (str_contains(',updator_id,updatorId,updated_id,updatedId,', $needle)) {
                $data[$field] = $user_id;
            } elseif (str_contains(',creator_name,creatorName,created_name,createdName,', $needle)) {
                $data[$field] = $user_name;
            } elseif (str_contains(',updator_name,updatorName,updated_name,updatedName,', $needle)) {
                $data[$field] = $user_name;
            } elseif (str_contains(',created_date,createdDate,updated_date,updatedDate,', $needle)) {
                $data[$field] = (int)date('ymd', $current_time);
            } elseif (str_contains(',created_by,createdBy,updated_by,updatedBy', $needle)) {
                $data[$field] = in_array($field, $modelManager->getIntFields(static::class), true) ? $user_id
                    : $user_name;
            }
        }

        return $data;
    }

    public function getAutoUpdatedData(): array
    {
        $current_time = time();

        $identity = Container::get(IdentityInterface::class);
        $user_id = $identity->getId(0);
        $user_name = $identity->getName('');
        $modelManager = Container::get(ModelManagerInterface::class);

        $dateFormat = $modelManager->getDateFormat(static::class);

        $data = [];
        foreach ($modelManager->getFields(static::class) as $field) {
            $needle = ",$field,";
            if (str_contains(',updated_time,updatedTime,updated_at,updatedAt,', $needle)) {
                $data[$field] = date($dateFormat, $current_time);
            } elseif (str_contains(',updator_id,updatorId,updated_id,updatedId,', $needle)) {
                $data[$field] = $user_id;
            } elseif (str_contains(',updator_name,updatorName,updated_name,updatedName,', $needle)) {
                $data[$field] = $user_name;
            } elseif (str_contains(',updated_date,updatedDate,', $needle)) {
                $data[$field] = (int)date('ymd', $current_time);
            } elseif (str_contains(',updated_by,updatedBy', $needle)) {
                $data[$field] = in_array($field, $modelManager->getIntFields(static::class), true) ? $user_id
                    : $user_name;
            }
        }

        return $data;
    }

    /**
     * Inserts or updates a model instance. Returning true on success or false otherwise.
     *
     * @param ?array $fields =model_fields(new static)
     *
     * @return static
     */
    public function save(?array $fields = null): static
    {
        if ($fields) {
            $this->load($fields);
        }

        $primaryKey = Container::get(ModelManagerInterface::class)->getPrimaryKey(static::class);
        if ($this->_snapshot || $this->$primaryKey) {
            return $this->update();
        } else {
            return $this->create();
        }
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

    public function with(string|array $withs): static
    {
        $relationManager = Container::get(RelationManagerInterface::class);

        $relationManager->earlyLoad(static::class, [$this], $withs);
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
        foreach (Container::get(ModelManagerInterface::class)->getFields(static::class) as $field) {
            if (isset($snapshot[$field])) {
                if ($this->{$field} !== $snapshot[$field]) {
                    $changed[] = $field;
                }
            } elseif ($this->$field !== null) {
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
     * @return \ManaPHP\Query\QueryInterface <static>
     */
    public static function query(?string $alias = null): QueryInterface
    {
        $query = Container::get(ThoseInterface::class)->get(static::class)->newQuery();

        return $alias ? $query->from(static::class, $alias) : $query;
    }

    /**
     * @param array $filters =model_var(new static)
     *
     * @return \ManaPHP\Query\QueryInterface <static>
     */
    public static function where(array $filters): QueryInterface
    {
        return static::select()->where($filters);
    }

    /**
     * @param array $filters =model_var(new static)
     *
     * @return \ManaPHP\Query\QueryInterface <static>
     */
    public static function search(array $filters): QueryInterface
    {
        return static::select()->search($filters);
    }

    /**
     * @param string $name
     *
     * @return \ManaPHP\Model\ModelInterface|\ManaPHP\Model\ModelInterface[]|mixed
     * @throws \ManaPHP\Exception\UnknownPropertyException
     */
    public function __get(mixed $name): mixed
    {
        $method = 'get' . ucfirst($name);
        if (method_exists($this, $method)) {
            return $this->$name = $this->$method()->fetch();
        } elseif (Container::has($name)) {
            return $this->{$name} = Container::get($name);
        } elseif (($relationManager = Container::get(RelationManagerInterface::class))->has(static::class, $name)) {
            return $this->$name = $relationManager->lazyLoad($this, $name)->fetch();
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
            $relationManager = Container::get(RelationManagerInterface::class);

            $relation = lcfirst(substr($name, 3));
            if ($relationManager->has(static::class, $relation)) {
                return $relationManager->lazyLoad($this, $relation);
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

        foreach (Container::get(ModelManagerInterface::class)->getFields(static::class) as $field) {
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

    /**
     * @return string
     */
    public function __toString()
    {
        return json_stringify($this->toArray());
    }
}