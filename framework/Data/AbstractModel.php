<?php
declare(strict_types=1);

namespace ManaPHP\Data;

use ArrayAccess;
use JsonSerializable;
use ManaPHP\Component;
use ManaPHP\Data\Db\SqlFragmentable;
use ManaPHP\Data\Model\Expression\Decrement;
use ManaPHP\Data\Model\Expression\Increment;
use ManaPHP\Data\Model\NotFoundException;
use ManaPHP\Data\Model\SerializeNormalizable;
use ManaPHP\Data\Relation\BelongsTo;
use ManaPHP\Data\Relation\HasMany;
use ManaPHP\Data\Relation\HasManyOthers;
use ManaPHP\Data\Relation\HasManyToMany;
use ManaPHP\Data\Relation\HasOne;
use ManaPHP\Data\Relation\ManagerInterface as RelationManager;
use ManaPHP\Event\ManagerInterface as EventManager;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\InvalidJsonException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Exception\UnknownPropertyException;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Identifying\IdentityInterface;
use ManaPHP\Validating\Validator\ValidateFailedException;
use ManaPHP\Validating\ValidatorInterface;
use ReflectionClass;

abstract class AbstractModel extends AbstractTable implements ModelInterface, ArrayAccess, JsonSerializable
{
    protected false|array $_snapshot = [];
    protected float $_last_refresh = 0;

    public function __construct(array $data = [])
    {
        if ($data) {
            foreach ($this->jsonFields() as $field) {
                if (isset($data[$field]) && is_string($data[$field])) {
                    $value = $data[$field];
                    $data[$field] = $value === '' ? [] : json_parse($value);
                }
            }

            $this->_snapshot = $data;

            foreach ($data as $field => $value) {
                $this->{$field} = $value;
            }
        }
    }

    protected function inferPrimaryKey(string $class): ?string
    {
        $fields = $this->fields();

        if (in_array('id', $fields, true)) {
            return 'id';
        }

        $prefix = lcfirst(($pos = strrpos($class, '\\')) === false ? $class : substr($class, $pos + 1));
        if (in_array($tryField = $prefix . '_id', $fields, true)) {
            return $tryField;
        } elseif (in_array($tryField = $prefix . 'Id', $fields, true)) {
            return $tryField;
        }

        $table = $this->table();
        if (($pos = strpos($table, ':')) !== false) {
            $table = substr($table, 0, $pos);
        } elseif (($pos = strpos($table, ',')) !== false) {
            $table = substr($table, 0, $pos);
        }

        $prefix = (($pos = strpos($table, '.')) ? substr($table, $pos + 1) : $table);
        if (in_array($tryField = $prefix . '_id', $fields, true)) {
            return $tryField;
        } elseif (in_array($tryField = $prefix . 'Id', $fields, true)) {
            return $tryField;
        }

        return null;
    }

    /**
     * @return string
     */
    public function foreignedKey(): string
    {
        $primaryKey = $this->primaryKey();
        if ($primaryKey !== 'id') {
            return $primaryKey;
        }

        $table = $this->table();

        if (($pos = strpos($table, '.')) !== false) {
            $table = substr($table, $pos + 1);
        }

        if (($pos = strpos($table, ':')) !== false) {
            $key = substr($table, 0, $pos) . '_id';
        } else {
            $key = $table . '_id';
        }

        return $key;
    }

    /**
     * @return string|null =model_field(new static)
     */
    public function autoIncrementField(): ?string
    {
        return $this->primaryKey();
    }

    /**
     * @param string $field =model_field(new static)
     *
     * @return bool
     */
    public function hasField(string $field): bool
    {
        return in_array($field, $this->fields(), true);
    }

    /**
     * @param string $field =model_field(new static)
     *
     * @return string
     */
    public function dateFormat(string $field): string
    {
        if (isset($this->_snapshot[$field])) {
            $ts = is_numeric($this->_snapshot[$field]);
        } elseif (isset($this->$field)) {
            $ts = is_numeric($this->$field);
        } else {
            $ts = in_array($field, $this->intFields(), true);
        }
        return $ts ? 'U' : 'Y-m-d H:i:s';
    }

    /**
     * @return array =model_fields(new static)
     */
    public function safeFields(): array
    {
        return array_keys($this->rules());
    }

    /**
     * @return array =model_fields(new static)
     */
    public function jsonFields(): array
    {
        return [];
    }

    /**
     * map model fields to table column
     *
     * @return array =model_var(new static)
     */
    public function mapFields(): array
    {
        return [];
    }

    /**
     * @return array =model_var(new static) ?: [$field => \PHPSTORM_META\validator_rule()]
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * @return array =model_var(new static)
     */
    public function labels(): array
    {
        return [];
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param array  $filters =model_var(new static)
     * @param ?array $options =['order'=>model_var(new static) ?: [$k=>SORT_ASC, $k2=>SORT_DESC], 'index'=>model_var(new static)]
     * @param ?array $fields  =model_fields(new static)
     *
     * @return  static[]
     */
    public static function all(array $filters = [], ?array $options = null, ?array $fields = null): array
    {
        return static::select($fields)->where($filters)->options($options)->fetch();
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param array  $filters =model_var(new static)
     * @param ?array $options =['order'=>model_var(new static) ?: [$k=>SORT_ASC, $k2=>SORT_DESC], 'index'=>model_var(new static)]
     * @param ?array $fields  =model_fields(new static)
     *
     * @return  \ManaPHP\Data\Paginator
     */
    public static function paginate(array $filters = [], ?array $options = null, ?array $fields = null
    ): Paginator {
        return static::select($fields)->search($filters)->options($options)->paginate();
    }

    /**
     * @param string|array $fields  =model_fields(new static) ?? model_field(new static)
     * @param array|null   $filters =model_var(new static)
     *
     * @return array
     */
    public static function lists(string|array $fields, ?array $filters = null): array
    {
        if (is_string($fields)) {
            $fields = [$fields];
        }

        $sample = static::sample();

        $keyField = $sample->primaryKey();
        if (!in_array($keyField, $fields, true)) {
            array_unshift($fields, $keyField);
        }

        if ($sample->hasField('display_order')) {
            $order = ['display_order' => SORT_DESC, $keyField => SORT_ASC];
        } else {
            $order = [$keyField => SORT_ASC];
        }
        return static::select($fields)->where($filters)->orderBy($order)->execute();
    }

    public static function get(int|string $id, ?int $ttl = null): static
    {
        $sample = static::sample();
        if ($ttl <= 0) {
            return static::firstOrFail([$sample->primaryKey() => $id]);
        }

        $key = __FILE__ . ':' . static::class . ":get:$id:$ttl";

        $r = apcu_fetch($key, $success);
        if (!$success) {
            $r = static::firstOrFail([$sample->primaryKey() => $id]);
            apcu_store($key, $r, $ttl);
        }

        return $r;
    }

    /**
     * @param array   $fields =model_fields(new static)
     * @param ?string $alias
     *
     * @return \ManaPHP\Data\QueryInterface <static>
     */
    public static function select(array $fields = [], ?string $alias = null): QueryInterface
    {
        return static::query($alias)->select($fields);
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

    public static function rId(): int|string
    {
        $sample = static::sample();

        /** @noinspection OneTimeUseVariablesInspection */
        $request = $sample->getShared(RequestInterface::class);

        return $request->getId($sample->primaryKey());
    }

    /**
     * @param ?array $fields =model_fields(new static)
     *
     * @return static
     */
    public static function rGet(?array $fields = null): static
    {
        $sample = static::sample();
        return static::firstOrFail([$sample->primaryKey() => static::rId()], $fields);
    }

    /**
     * Allows to query the last record that match the specified conditions
     *
     * @param ?array $filters =model_var(new static)
     * @param ?array $fields  =model_fields(new static)
     *
     * @return static|null
     */
    public static function last(?array $filters = null, ?array $fields = null): ?static
    {
        $sample = static::sample();

        $primaryKey = $sample->primaryKey();
        $rs = static::select($fields)->where($filters)->orderBy([$primaryKey => SORT_DESC])->limit(1)->fetch();
        return $rs[0] ?? null;
    }

    /**
     * @param array  $filters =model_var(new static)
     * @param string $field   =model_field(new static)
     * @param ?int   $ttl
     *
     * @return int|float|string|null
     */
    public static function value(array $filters, string $field, ?int $ttl = null): mixed
    {
        if ($ttl !== null && !is_int($ttl)) {
            throw new MisuseException('ttl must be a integer');
        }

        $sample = static::sample();
        $pkName = $sample->primaryKey();

        $pkValue = null;

        if (count($filters) === 1 && isset($filters[$pkName])) {
            $pkValue = $filters[$pkName];
        }

        if ($ttl === null || $pkValue === null) {
            $rs = static::select([$field])->where($filters)->limit(1)->execute();
            return $rs ? $rs[0][$field] : null;
        }

        $key = __FILE__ . ':' . static::class . ":value:$field:$pkValue:$ttl";

        $value = apcu_fetch($key, $success);
        if (!$success) {
            $rs = static::select([$field])->whereEq($pkName, $pkValue)->limit(1)->execute();
            $value = $rs ? $rs[0][$field] : null;

            apcu_store($key, $value, $ttl);
        }

        return $value;
    }

    /**
     * @param array  $filters =model_var(new static)
     * @param string $field   =model_field(new static)
     * @param ?int   $ttl
     *
     * @return int|float|string
     */
    public static function valueOrFail(array $filters, string $field, ?int $ttl = null): mixed
    {
        $value = static::value($filters, $field, $ttl);
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
     * @param ?array $filters =model_var(new static)
     *
     * @return array
     */
    public static function values(string $field, ?array $filters = null): array
    {
        return static::where($filters)->orderBy([$field => SORT_ASC])->values($field);
    }

    /**
     * @param string $field   =model_field(new static)
     * @param ?array $filters =model_var(new static)
     *
     * @return array
     */
    public static function kvalues(string $field, ?array $filters = null): array
    {
        $keyField = static::sample()->primaryKey();
        $valueField = $field;

        $kvalues = [];
        foreach (static::select([$keyField, $valueField])->where($filters)->execute() as $v) {
            $kvalues[$v[$keyField]] = $v[$valueField];
        }

        return $kvalues;
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
     * @param array        $filters =model_var(new static)
     * @param array        $aggregation
     * @param string|array $options
     *
     * @return array
     */
    public static function aggregate(array $filters, array $aggregation, mixed $options = null): array
    {
        if (is_string($options)) {
            if (str_contains($options, ',')) {
                $options = ['group' => $options];
            } else {
                $options = ['group' => $options, 'index' => $options];
            }
        }
        return static::where($filters)->options($options)->aggregate($aggregation);
    }

    /**
     * @param ?array $filters =model_var(new static)
     * @param string $field   =model_field(new static)
     *
     * @return int
     */
    public static function count(?array $filters = null, string $field = '*'): int
    {
        return static::where($filters)->count($field);
    }

    /**
     * Allows to calculate a summary on a field that match the specified conditions
     *
     * @param string $field   =model_field(new static)
     * @param ?array $filters =model_var(new static)
     *
     * @return int|float|null
     */
    public static function sum(string $field, array $filters = null): mixed
    {
        return static::where($filters)->sum($field);
    }

    /**
     * Allows to get the max value of a column that match the specified conditions
     *
     * @param string $field   =model_field(new static)
     * @param ?array $filters =model_var(new static)
     *
     * @return int|float|null
     */
    public static function max(string $field, ?array $filters = null): mixed
    {
        return static::where($filters)->max($field);
    }

    /**
     * Allows to get the min value of a column that match the specified conditions
     *
     *
     * @param string $field   =model_field(new static)
     * @param ?array $filters =model_var(new static)
     *
     * @return int|float|null
     */
    public static function min(string $field, array $filters = null): mixed
    {
        return static::where($filters)->min($field);
    }

    /**
     * Allows to calculate the average value on a column matching the specified conditions
     *
     * @param string $field   =model_field(new static)
     * @param ?array $filters =model_var(new static)
     *
     * @return float|null
     */
    public static function avg(string $field, ?array $filters = null): ?float
    {
        return (float)static::where($filters)->avg($field);
    }

    /**
     * Assigns values to a model from an array
     *
     * @param array|\ManaPHP\Data\ModelInterface $data   =model_var(new static)
     * @param array                              $fields =model_fields(static::class)
     *
     * @return static
     */
    public function assign(array|ModelInterface $data, array $fields): static
    {
        if ($data instanceof self) {
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
        $fields = $fields ?? $this->safeFields();

        $request = $this->getShared(RequestInterface::class);

        $data = $request->get();

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

    /**
     * @param ?array $fields =model_fields(new static)
     *
     * @return void
     */
    public function validate(?array $fields = null): void
    {
        if (!$rules = $this->rules()) {
            return;
        }

        if (isset($rules[0])) {
            throw new MisuseException(['`%s` rules must be an associative array', static::class]);
        }

        $validator = $this->getShared(ValidatorInterface::class);

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

        $validator = $this->getShared(ValidatorInterface::class);

        $this->$field = $validator->validateModel($field, $this, $rules);
    }

    public function getAutoCreatedData(): array
    {
        $current_time = time();

        $identity = $this->getShared(IdentityInterface::class);
        $user_id = $identity->getId(0);
        $user_name = $identity->getName('');

        $data = [];
        foreach ($this->fields() as $field) {
            if ($this->$field !== null) {
                continue;
            }

            $needle = ",$field,";
            if (str_contains(',created_time,createdTime,created_at,createdAt,', $needle)) {
                $data[$field] = date($this->dateFormat($field), $current_time);
            } elseif (str_contains(',updated_time,updatedTime,updated_at,updatedAt,', $needle)) {
                $data[$field] = date($this->dateFormat($field), $current_time);
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
                $data[$field] = in_array($field, $this->intFields(), true) ? $user_id : $user_name;
            }
        }

        return $data;
    }

    public function getAutoUpdatedData(): array
    {
        $current_time = time();

        $identity = $this->getShared(IdentityInterface::class);
        $user_id = $identity->getId(0);
        $user_name = $identity->getName('');

        $data = [];
        foreach ($this->fields() as $field) {
            $needle = ",$field,";
            if (str_contains(',updated_time,updatedTime,updated_at,updatedAt,', $needle)) {
                $data[$field] = date($this->dateFormat($field), $current_time);
            } elseif (str_contains(',updator_id,updatorId,updated_id,updatedId,', $needle)) {
                $data[$field] = $user_id;
            } elseif (str_contains(',updator_name,updatorName,updated_name,updatedName,', $needle)) {
                $data[$field] = $user_name;
            } elseif (str_contains(',updated_date,updatedDate,', $needle)) {
                $data[$field] = (int)date('ymd', $current_time);
            } elseif (str_contains(',updated_by,updatedBy', $needle)) {
                $data[$field] = in_array($field, $this->intFields(), true) ? $user_id : $user_name;
            }
        }

        return $data;
    }

    /**
     * @param ?array $fields =model_fields(new static)
     *
     * @return static
     */
    public static function rCreate(?array $fields = null): static
    {
        return (new static())->load($fields)->create();
    }

    /**
     * @param ?array $fields =model_fields(new static)
     *
     * @return static
     */
    public static function rUpdate(?array $fields = null): static
    {
        return static::rGet()->load($fields)->update();
    }

    protected function existsInternal(): bool
    {
        $primaryKey = $this->primaryKey();
        if ($this->$primaryKey === null) {
            return false;
        } else {
            return $this->newQuery()->where([$primaryKey => $this->$primaryKey])->forceUseMaster()->exists();
        }
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

        $primaryKey = $this->primaryKey();
        if ($this->_snapshot || $this->$primaryKey) {
            return $this->update();
        } else {
            return $this->create();
        }
    }

    public static function rDelete(): static
    {
        return static::rGet()->delete();
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
        $relationManager = $this->getShared(RelationManager::class);

        $relationManager->earlyLoad($this, [$this], $withs);
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
        $model->_snapshot = false;

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
        $model->_snapshot = false;

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
        foreach ($this->fields() as $field) {
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

    public function fireEvent(string $event, mixed $data = null): void
    {
        $eventManager = $this->getShared(EventManager::class);

        $eventManager->fireEvent($event, $data, $this);
    }

    /**
     * @param float  $interval
     * @param ?array $fields =model_fields(new static)
     *
     * @return static
     */
    public function refresh(float $interval, ?array $fields = null): static
    {
        if ($interval > 0) {
            if ($this->_last_refresh && microtime(true) - $this->_last_refresh < $interval) {
                return $this;
            }
            $this->_last_refresh = microtime(true);
        }

        $primaryKey = $this->primaryKey();
        $r = $this->newQuery()->select($fields)->where([$primaryKey => $this->$primaryKey])->execute();
        if (!$r) {
            throw new NotFoundException(static::class, [$primaryKey => $this->$primaryKey]);
        }

        $data = (array)$r[0];
        foreach ($this->jsonFields() as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                if ($data[$field] === '') {
                    $data[$field] = [];
                } elseif (($json = json_parse($data[$field])) === null) {
                    throw new InvalidJsonException(['`%s` field of `%s` is not a json string', $field, static::class]);
                } else {
                    $data[$field] = $json;
                }
            }
        }

        foreach ($data as $field => $value) {
            $this->$field = $value;
        }

        $this->_snapshot = array_merge($this->_snapshot, $data);

        return $this;
    }

    public function relations(): array
    {
        return [];
    }

    public static function constants(string $name, ?bool $comment = false): array
    {
        $name = strtoupper($name) . '_';
        $constants = [];

        $rClass = new ReflectionClass(static::class);
        $file = $comment ? file_get_contents($rClass->getFileName()) : '';
        foreach ($rClass->getConstants() as $cName => $cValue) {
            if (str_starts_with($cName, $name)) {
                if ($comment
                    && preg_match('#\s+const\s+' . $cName . '\s*=[^/]+//(<([^>\r\n]+)>|[^\s]+)#', $file, $match)
                ) {
                    $constants[$cValue] = trim($match[2] ?? $match[1]);
                } else {
                    $constants[$cValue] = strtolower(substr($cName, strlen($name)));
                }
            }
        }

        if (!$constants) {
            throw new MisuseException(['starts with `:1` constants is not exists in `:2` model', $name, static::class]);
        }

        return $constants;
    }

    /**
     * @param string    $field =model_field(new static)
     * @param int|float $step
     *
     * @return static
     */
    public function increment(string $field, int|float $step = 1): static
    {
        if (!$this->hasField($field)) {
            throw new InvalidArgumentException([':field field is invalid.', 'field' => $field]);
        }

        $this->$field = new Increment($step);

        return $this;
    }

    /**
     * @param string    $field =model_field(new static)
     * @param int|float $step
     *
     * @return static
     */
    public function decrement(string $field, int|float $step = 1): static
    {
        if (!$this->hasField($field)) {
            throw new InvalidArgumentException([':field field is invalid.', 'field' => $field]);
        }

        $this->$field = new Decrement($step);

        return $this;
    }

    /**
     * @param ?string $alias
     *
     * @return \ManaPHP\Data\QueryInterface <static>
     */
    public static function query(?string $alias = null): QueryInterface
    {
        $query = static::sample()->newQuery();

        return $alias ? $query->from(static::class, $alias) : $query;
    }

    /**
     * @param array $filters =model_var(new static)
     *
     * @return \ManaPHP\Data\QueryInterface <static>
     */
    public static function where(array $filters): QueryInterface
    {
        return static::select()->where($filters);
    }

    /**
     * @param array $filters =model_var(new static)
     *
     * @return \ManaPHP\Data\QueryInterface <static>
     */
    public static function search(array $filters): QueryInterface
    {
        return static::select()->search($filters);
    }

    public function delete(): static
    {
        $primaryKey = $this->primaryKey();

        if ($this->$primaryKey === null) {
            throw new MisuseException('missing primary key value');
        }

        list($db, $table) = $this->getUniqueShard($this);

        $this->fireEvent('model:deleting');

        /** @var DbInterface $dbInstance */
        $dbInstance = $this->getShared($db);

        $dbInstance->delete($table, [$primaryKey => $this->$primaryKey]);

        $this->fireEvent('model:deleted');

        return $this;
    }

    /**
     * @param string  $thatModel
     * @param ?string $thisField =model_field(new static)
     *
     * @return \ManaPHP\Data\Relation\BelongsTo
     */
    public function belongsTo(string $thatModel, ?string $thisField = null): BelongsTo
    {
        /** @var \ManaPHP\Data\ModelInterface $thatClass */
        $thatClass = $thatModel;
        $that = $thatClass::sample();

        return new BelongsTo(static::class, $thisField ?? $that->foreignedKey(), $thatModel, $that->primaryKey());
    }

    /**
     * @param string  $thatModel
     * @param ?string $thatField =model_field(new static)
     *
     * @return \ManaPHP\Data\Relation\HasOne
     */
    public function hasOne(string $thatModel, ?string $thatField = null): HasOne
    {
        return new HasOne(static::class, $this->primaryKey(), $thatModel, $thatField ?? $this->foreignedKey());
    }

    /**
     * @param string  $thatModel
     * @param ?string $thatField =model_field(new static)
     *
     * @return \ManaPHP\Data\Relation\HasMany
     */
    public function hasMany(string $thatModel, ?string $thatField = null): HasMany
    {
        return new HasMany(static::class, $this->primaryKey(), $thatModel, $thatField ?? $this->foreignedKey());
    }

    public function hasManyToMany(string $thatModel, string $pivotModel): HasManyToMany
    {
        /** @var \ManaPHP\Data\ModelInterface $thatClass */
        $thatClass = $thatModel;
        $that = $thatClass::sample();

        return new HasManyToMany(
            static::class, $this->primaryKey(), $thatModel, $that->primaryKey(),
            $pivotModel, $this->foreignedKey(), $that->foreignedKey()
        );
    }

    /**
     * @param string  $thatModel
     * @param ?string $thisFilter =model_field(new static)
     *
     * @return \ManaPHP\Data\Relation\HasManyOthers
     */
    public function hasManyOthers(string $thatModel, ?string $thisFilter = null): HasManyOthers
    {
        /** @var \ManaPHP\Data\ModelInterface $thatClass */
        $thatClass = $thatModel;
        $that = $thatClass::sample();

        $foreingedKey = $that->foreignedKey();

        if ($thisFilter === null) {
            $keys = [];
            foreach ($this->fields() as $field) {
                if ($field === $foreingedKey || $field === 'id' || $field === '_id') {
                    continue;
                }

                if (!str_ends_with($field, '_id') && !str_ends_with($field, 'Id')) {
                    continue;
                }

                if (in_array($field, ['updator_id', 'creator_id'], true)) {
                    continue;
                }

                $keys[] = $field;
            }

            if (count($keys) === 1) {
                $thisFilter = $keys[0];
            } else {
                throw new MisuseException('$thisValue must be not null');
            }
        }

        return new HasManyOthers(
            static::class, $thisFilter, $that->foreignedKey(), $thatModel, $that->primaryKey()
        );
    }

    public function belongsToMany(string $thatModel, string $pivotModel): HasManyToMany
    {
        return $this->hasManyToMany($thatModel, $pivotModel);
    }

    public function getNew(string $class, array $params = []): mixed
    {
        return $this->_container->make($class, $params);
    }

    /**
     * @param string $name
     *
     * @return \ManaPHP\Data\ModelInterface|\ManaPHP\Data\ModelInterface[]|mixed
     * @throws \ManaPHP\Exception\UnknownPropertyException
     */
    public function __get(mixed $name): mixed
    {
        if ($name === '_container') {
            return $this->_container = container();
        }

        $method = 'get' . ucfirst($name);
        if (method_exists($this, $method)) {
            return $this->$name = $this->$method()->fetch();
        } elseif ($this->_container->has($name)) {
            return $this->{$name} = $this->getShared($name);
        } elseif (($relationManager = $this->getShared(RelationManager::class))->has($this, $name)) {
            return $this->$name = $relationManager->lazyLoad($this, $name)->fetch();
        } else {
            throw new UnknownPropertyException(['`%s` does not contain `%s` field.`', static::class, $name]);
        }
    }

    public function __set(mixed $name, mixed $value): void
    {
        if (is_scalar($value)) {
            throw new MisuseException(['`%s` Model does\'t contains `%s` field', static::class, $name]);
        }

        $this->$name = $value;
    }

    public function __isset(mixed $name): bool
    {
        return isset($this->$name);
    }

    public function __call(string $name, array $arguments): mixed
    {
        if (str_starts_with($name, 'get')) {
            $relationManager = $this->getShared(RelationManager::class);

            $relation = lcfirst(substr($name, 3));
            if ($relationManager->has($this, $relation)) {
                return $relationManager->lazyLoad($this, $relation);
            } else {
                throw new NotSupportedException(['`%s` model does not define `%s` relation', static::class, $relation]);
            }
        }
        throw new NotSupportedException(['`%s` does not contain `%s` method', static::class, $name]);
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        $data = [];

        foreach (get_object_vars($this) as $field => $value) {
            if (in_array($field, ['_container', '_snapshot', '_last_refresh'], true)) {
                continue;
            }

            if ($value instanceof Component) {
                continue;
            }

            $data[$field] = $value;
        }

        if ($changedFields = $this->getChangedFields()) {
            $data['*changed_fields*'] = $changedFields;
        }

        foreach ($this->fields() as $field) {
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

    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    public function offsetUnset($offset)
    {
        $this->$offset = null;
    }

    public function jsonSerialize()
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