<?php

namespace ManaPHP\Data;

use ArrayAccess;
use JsonSerializable;
use ManaPHP\Aop\Unaspectable;
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
use ManaPHP\Di;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\InvalidJsonException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Exception\ParameterOrderException;
use ManaPHP\Exception\UnknownPropertyException;
use ManaPHP\Validating\Validator\ValidateFailedException;
use ReflectionClass;
use Serializable;

abstract class Model extends Table implements ModelInterface, Serializable, ArrayAccess, JsonSerializable, Unaspectable
{
    /**
     * @var array
     */
    protected $_snapshot = [];

    /**
     * @var float
     */
    protected $_last_refresh = 0;

    /**
     * @param array $data
     */
    public function __construct($data = [])
    {
        if ($data) {
            foreach ($this->getJsonFields() as $field) {
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

    /**
     * @param string $class
     *
     * @return string|null
     */
    protected function _inferPrimaryKey($class)
    {
        $fields = $this->getFields();

        if (in_array('id', $fields, true)) {
            return 'id';
        }

        $prefix = lcfirst(($pos = strrpos($class, '\\')) === false ? $class : substr($class, $pos + 1));
        if (in_array($tryField = $prefix . '_id', $fields, true)) {
            return $tryField;
        } elseif (in_array($tryField = $prefix . 'Id', $fields, true)) {
            return $tryField;
        }

        $table = $this->getTable();
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
    public function getForeignedKey()
    {
        $primaryKey = $this->getPrimaryKey();
        if ($primaryKey !== 'id') {
            return $primaryKey;
        }

        $table = $this->getTable();

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
    public function getAutoIncrementField()
    {
        $primaryKey = $this->getPrimaryKey();
        return is_string($primaryKey) ? $primaryKey : null;
    }

    /**
     * @param string $field =model_field(new static)
     *
     * @return bool
     */
    public function hasField($field)
    {
        return in_array($field, $this->getFields(), true);
    }

    /**
     * @param string $field =model_field(new static)
     *
     * @return string
     */
    public function getDateFormat($field)
    {
        if (isset($this->_snapshot[$field])) {
            $ts = is_numeric($this->_snapshot[$field]);
        } elseif (isset($this->$field)) {
            $ts = is_numeric($this->$field);
        } else {
            $ts = in_array($field, $this->getIntFields(), true);
        }
        return $ts ? 'U' : 'Y-m-d H:i:s';
    }

    /**
     * @return array =model_fields(new static)
     */
    public function getSafeFields()
    {
        return array_keys($this->rules());
    }

    /**
     * @return array =model_fields(new static)
     */
    public function getJsonFields()
    {
        return [];
    }

    /**
     * @return array =model_var(new static) ?: [$field => \PHPSTORM_META\validator_rule()]
     */
    public function rules()
    {
        return [];
    }

    /**
     * @return array =model_var(new static)
     */
    public function labels()
    {
        return [];
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param array $filters =model_var(new static)
     * @param array $options =['order'=>model_var(new static) ?: [$k=>SORT_ASC, $k2=>SORT_DESC],
     *                       'index'=>model_var(new static)]
     * @param array $fields  =model_fields(new static)
     *
     * @return  static[]
     */
    public static function all($filters = [], $options = null, $fields = null)
    {
        return static::select($fields)->where($filters)->options($options)->fetch();
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param array $filters =model_var(new static)
     * @param array $options =['order'=>model_var(new static) ?: [$k=>SORT_ASC, $k2=>SORT_DESC],
     *                       'index'=>model_var(new static)]
     * @param array $fields  =model_fields(new static)
     *
     * @return  \ManaPHP\Data\Paginator
     */
    public static function paginate($filters = [], $options = null, $fields = null)
    {
        return static::select($fields)->search($filters)->options($options)->paginate();
    }

    /**
     * @param string|array $fields  =model_var(new static) ?: model_field(new static) ?:
     *                              [$k=>model_field(new static)]
     * @param array        $filters =model_var(new static)
     *
     * @return array
     */
    public static function lists($fields, $filters = null)
    {
        $sample = static::sample();

        if (is_string($fields)) {
            $keyField = $sample->getPrimaryKey();

            $query = static::select([$keyField, $fields])->where($filters);
            if ($sample->hasField('display_order')) {
                return $query->orderBy(['display_order' => SORT_DESC, $keyField => SORT_ASC])->execute();
            } else {
                return $query->orderBy([$keyField => SORT_ASC])->execute();
            }
        } elseif (isset($fields[0])) {
            $keyField = $sample->getPrimaryKey();
            array_unshift($fields, $keyField);

            if ($sample->hasField('display_order')) {
                $order = ['display_order' => SORT_DESC, $keyField => SORT_ASC];
            } else {
                $order = [$keyField => SORT_ASC];
            }
            return static::select($fields)->where($filters)->orderBy($order)->execute();
        } else {
            $keyField = key($fields);
            $valueField = current($fields);

            $list = [];
            foreach (static::select([$keyField, $valueField])->where($filters)->execute() as $v) {
                $key = $v[$keyField];
                $value = $v[$valueField];

                if (!isset($list[$key])) {
                    $list[$key] = $value;
                } elseif (is_array($list[$key])) {
                    $list[$key][] = $value;
                } else {
                    $list[$key] = [$list[$key], $value];
                }
            }

            return $list;
        }
    }

    /**
     * @param int|string $id
     * @param int|array  $fieldsOrTtl =model_fields(new static)
     *
     * @return static
     */
    public static function get($id, $fieldsOrTtl = null)
    {
        if (!is_scalar($id)) {
            throw new InvalidValueException('Model::get id is not scalar');
        }

        $sample = static::sample();

        if (!is_int($fieldsOrTtl)) {
            if (!$rs = static::select($fieldsOrTtl)->whereEq($sample->getPrimaryKey(), $id)->limit(1)->fetch()) {
                throw new NotFoundException(static::class, $id);
            } else {
                return $rs[0];
            }
        }

        $ttl = $fieldsOrTtl;
        $key = '_mp:models:' . static::class . ":get:$id:$ttl";

        $r = apcu_fetch($key, $success);
        if (!$success) {
            if (!$rs = static::select()->whereEq($sample->getPrimaryKey(), $id)->limit(1)->fetch()) {
                throw new NotFoundException(static::class, $id);
            }

            $r = $rs[0];

            apcu_store($key, $r, $ttl);
        }

        return $r;
    }

    /**
     * @param array|string $fields =model_fields(new static)
     * @param string       $alias
     *
     * @return \ManaPHP\Data\QueryInterface <static>
     */
    public static function select($fields = [], $alias = null)
    {
        return static::query($alias)->select($fields);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param int|string|array $filters =model_var(new static)
     * @param array            $fields  =model_fields(new static)
     *
     * @return static|null
     */
    public static function first($filters, $fields = null)
    {
        if ($filters === null) {
            throw new MisuseException('Model:first is not support null value filters');
        }

        $rs = static::select($fields)
            ->where(is_scalar($filters) ? [static::sample()->getPrimaryKey() => $filters] : $filters)
            ->limit(1)->fetch();
        return $rs[0] ?? null;
    }

    /**
     * @param int|string|array $filters =model_var(new static)
     * @param array            $fields  =model_fields(new static)
     *
     * @return static
     */
    public static function firstOrFail($filters, $fields = null)
    {
        $r = static::first($filters, $fields);
        if ($r === null) {
            throw new NotFoundException(static::class, $filters);
        }

        return $r;
    }

    /**
     * @return int|string
     */
    public static function rId()
    {
        $sample = static::sample();

        /** @var \ManaPHP\Http\RequestInterface $request */
        $request = $sample->getShared('request');

        return $request->getId($sample->getPrimaryKey());
    }

    /**
     * @param array $fields =model_fields(new static)
     *
     * @return static
     */
    public static function rGet($fields = null)
    {
        return static::get(static::rId(), $fields);
    }

    /**
     * Allows to query the last record that match the specified conditions
     *
     * @param array $filters =model_var(new static)
     * @param array $fields  =model_fields(new static)
     *
     * @return static|null
     */
    public static function last($filters = null, $fields = null)
    {
        $sample = static::sample();

        $primaryKey = $sample->getPrimaryKey();
        $rs = static::select($fields)->where($filters)->orderBy([$primaryKey => SORT_DESC])->limit(1)->fetch();
        return $rs[0] ?? null;
    }

    /**
     * @param int|string|array $filters =model_var(new static)
     * @param string           $field   =model_field(new static)
     * @param int              $ttl
     *
     * @return int|float|string|null
     */
    public static function value($filters, $field, $ttl = null)
    {
        if (!is_string($field)) {
            throw new ParameterOrderException(__METHOD__ . ' field');
        }

        if ($ttl !== null && !is_int($ttl)) {
            throw new MisuseException('ttl must be a integer');
        }

        $sample = static::sample();
        $pkName = $sample->getPrimaryKey();

        $pkValue = null;
        if (is_scalar($filters)) {
            $pkValue = $filters;
            $filters = [$pkName => $pkValue];
        } elseif (is_array($filters)) {
            if (count($filters) === 1 && isset($filters[$pkName])) {
                $pkValue = $filters[$pkName];
            }
        }

        if ($ttl === null || $pkValue === null) {
            $rs = static::select([$field])->where($filters)->limit(1)->execute();
            return $rs ? $rs[0][$field] : null;
        }

        $key = '_mp:models:' . static::class . ":value:$field:$pkValue:$ttl";

        $value = apcu_fetch($key, $success);
        if (!$success) {
            $rs = static::select([$field])->whereEq($pkName, $pkValue)->limit(1)->execute();
            $value = $rs ? $rs[0][$field] : null;

            apcu_store($key, $value, $ttl);
        }

        return $value;
    }

    /**
     * @param int|string|array $filters =model_var(new static)
     * @param string           $field   =model_field(new static)
     * @param int              $ttl
     *
     * @return int|float|string
     */
    public static function valueOrFail($filters, $field, $ttl = null)
    {
        $value = static::value($filters, $field, $ttl);
        if ($value === null) {
            throw new NotFoundException(static::class, $filters);
        } else {
            return $value;
        }
    }

    /**
     * @param int|string|array $filters =model_var(new static)
     * @param string|float|int $field   =model_field(new static)
     * @param mixed            $default
     *
     * @return float|int|string
     */
    public static function valueOrDefault($filters, $field, $default)
    {
        return ($value = static::value($filters, $field)) === null ? $default : $value;
    }

    /**
     * @param string $field   =model_field(new static)
     * @param array  $filters =model_var(new static)
     *
     * @return array
     */
    public static function values($field, $filters = null)
    {
        if (!is_string($field)) {
            throw new ParameterOrderException(__METHOD__ . ' field');
        }
        return static::where($filters)->orderBy([$field => SORT_ASC])->values($field);
    }

    /**
     * @param int|string|array $filters =model_var(new static)
     *
     * @return bool
     */
    public static function exists($filters)
    {
        $primaryKey = static::sample()->getPrimaryKey();

        return static::select()->where(is_scalar($filters) ? [$primaryKey => $filters] : $filters)->exists();
    }

    /**
     * @param array        $filters =model_var(new static)
     * @param array        $aggregation
     * @param string|array $options
     *
     * @return array
     */
    public static function aggregate($filters, $aggregation, $options = null)
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
     * Allows to count how many records match the specified conditions
     *
     * @param array  $filters =model_var(new static)
     * @param string $field   =model_field(new static)
     *
     * @return int
     */
    public static function count($filters = null, $field = '*')
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
    public static function sum($field, $filters = null)
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
    public static function max($field, $filters = null)
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
    public static function min($field, $filters = null)
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
    public static function avg($field, $filters = null)
    {
        return (float)static::where($filters)->avg($field);
    }

    /**
     * Assigns values to a model from an array
     *
     * @param array|\ManaPHP\Data\Model $data   =model_var(new static)
     * @param array                     $fields =model_fields(static::class)
     *
     * @return static
     */
    public function assign($data, $fields)
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
     * @param array $fields =model_fields(new static)
     *
     * @return static
     */
    public function load($fields = null)
    {
        $fields = $fields ?? $this->getSafeFields();

        /** @var \ManaPHP\Http\RequestInterface $request */
        $request = $this->getShared('request');

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
     * @param array $fields =model_fields(new static)
     *
     * @return void
     */
    public function validate($fields = null)
    {
        if (!$rules = $this->rules()) {
            return;
        }

        if (isset($rules[0])) {
            throw new MisuseException(['`%s` rules must be an associative array', static::class]);
        }

        /** @var \ManaPHP\Validating\ValidatorInterface $validator */
        $validator = $this->getShared('validator');

        $errors = [];

        foreach ($fields ?: $this->getChangedFields() as $field) {
            if (!isset($rules[$field]) || $this->$field instanceof SqlFragmentable) {
                continue;
            }

            try {
                $this->$field = $validator->validateModel($field, $this, $rules[$field]);
            } catch (ValidateFailedException $exception) {
                /** @noinspection AdditionOperationOnArraysInspection */
                $errors += $exception->getErrors();
            }
        }

        if ($errors) {
            throw new ValidateFailedException($errors);
        }
    }

    /**
     * @param string $field =model_field(new static)
     * @param array  $rules
     *
     * @return void
     */
    public function validateField($field, $rules = null)
    {
        if ($rules === null) {
            if (!isset($rules[$field])) {
                return;
            }

            $rules = $rules[$field];
        }

        /** @var \ManaPHP\Validating\ValidatorInterface $validator */
        $validator = $this->getShared('validator');

        $this->$field = $validator->validateModel($field, $this, $rules);
    }

    /**
     * @return  array
     */
    public function getAutoCreatedData()
    {
        $current_time = time();

        $identity = $this->getShared('identity');
        $user_id = $identity->getId(0);
        $user_name = $identity->getName('');

        $data = [];
        foreach ($this->getFields() as $field) {
            if ($this->$field !== null) {
                continue;
            }

            $needle = ",$field,";
            if (str_contains(',created_time,createdTime,created_at,createdAt,', $needle)) {
                $data[$field] = date($this->getDateFormat($field), $current_time);
            } elseif (str_contains(',updated_time,updatedTime,updated_at,updatedAt,', $needle)) {
                $data[$field] = date($this->getDateFormat($field), $current_time);
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
                $data[$field] = in_array($field, $this->getIntFields(), true) ? $user_id : $user_name;
            }
        }

        return $data;
    }

    /**
     * @return  array
     */
    public function getAutoUpdatedData()
    {
        $current_time = time();

        $identity = $this->getShared('identity');
        $user_id = $identity->getId(0);
        $user_name = $identity->getName('');

        $data = [];
        foreach ($this->getFields() as $field) {
            $needle = ",$field,";
            if (str_contains(',updated_time,updatedTime,updated_at,updatedAt,', $needle)) {
                $data[$field] = date($this->getDateFormat($field), $current_time);
            } elseif (str_contains(',updator_id,updatorId,updated_id,updatedId,', $needle)) {
                $data[$field] = $user_id;
            } elseif (str_contains(',updator_name,updatorName,updated_name,updatedName,', $needle)) {
                $data[$field] = $user_name;
            } elseif (str_contains(',updated_date,updatedDate,', $needle)) {
                $data[$field] = (int)date('ymd', $current_time);
            } elseif (str_contains(',updated_by,updatedBy', $needle)) {
                $data[$field] = in_array($field, $this->getIntFields(), true) ? $user_id : $user_name;
            }
        }

        return $data;
    }

    /**
     * @param array $fields =model_fields(new static)
     *
     * @return static
     */
    public static function rCreate($fields = null)
    {
        return (new static())->load($fields)->create();
    }

    /**
     * @param array $fields =model_fields(new static)
     *
     * @return static
     */
    public static function rUpdate($fields = null)
    {
        return static::rGet()->load($fields)->update();
    }

    /**
     * Checks if the current record already exists or not
     *
     * @return bool
     */
    protected function _exists()
    {
        $primaryKey = $this->getPrimaryKey();
        if ($this->$primaryKey === null) {
            return false;
        } else {
            return $this->newQuery()->where([$primaryKey => $this->$primaryKey])->forceUseMaster()->exists();
        }
    }

    /**
     * Inserts or updates a model instance. Returning true on success or false otherwise.
     *
     * @param array $fields =model_fields(new static)
     *
     * @return static
     */
    public function save($fields = null)
    {
        if ($fields) {
            $this->load($fields);
        }

        $primaryKey = $this->getPrimaryKey();
        if ($this->_snapshot || $this->$primaryKey) {
            return $this->update();
        } else {
            return $this->create();
        }
    }

    /**
     * @return static
     */
    public static function rDelete()
    {
        return static::rGet()->delete();
    }

    /**
     * @param array $fieldValues =model_var(new static)
     * @param array $filters     =model_var(new static)
     *
     * @return int
     */
    public static function updateAll($fieldValues, $filters)
    {
        return static::where($filters)->update($fieldValues);
    }

    /**
     * @param array $filters =model_var(new static)
     *
     * @return int
     */
    public static function deleteAll($filters)
    {
        return static::where($filters)->delete();
    }

    /**
     * @param string|array $withs
     *
     * @return static
     */
    public function with($withs)
    {
        /** @var \ManaPHP\Data\Relation\ManagerInterface $relationsManager */
        $relationsManager = $this->getShared('relationsManager');

        $relationsManager->earlyLoad($this, [$this], $withs, false);
        return $this;
    }

    /**
     * Returns the instance as an array representation
     *
     * @return array =model_var(new static)
     */
    public function toArray()
    {
        $data = [];

        foreach (get_object_vars($this) as $field => $value) {
            if ($field[0] === '_') {
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

            if ($value !== null) {
                $data[$field] = $value;
            }
        }

        return $data;
    }

    /**
     * @param array $fields =model_fields(new static)
     *
     * @return static
     */
    public function only($fields)
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
    public function except($fields)
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
    public function getSnapshotData()
    {
        return $this->_snapshot;
    }

    /**
     * Returns a list of changed values
     *
     * @return array =model_fields(new static)
     */
    public function getChangedFields()
    {
        $snapshot = $this->_snapshot;

        $changed = [];
        foreach ($this->getFields() as $field) {
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
     * @param string|array $fields =model_fields(new static)
     *
     * @return bool
     */
    public function hasChanged($fields)
    {
        $snapshot = $this->_snapshot;

        foreach ((array)$fields as $field) {
            if (!isset($snapshot[$field]) || $this->{$field} !== $snapshot[$field]) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $event
     * @param array  $data
     *
     * @return void
     */
    public function fireEvent($event, $data = [])
    {
        /** @var \ManaPHP\Event\ManagerInterface $eventsManager */
        $eventsManager = $this->getShared('eventsManager');

        $eventsManager->fireEvent($event, $data, $this);
    }

    /**
     * @param float $interval
     * @param array $fields =model_fields(new static)
     *
     * @return static
     */
    public function refresh($interval, $fields = null)
    {
        if ($interval > 0) {
            if ($this->_last_refresh && microtime(true) - $this->_last_refresh < $interval) {
                return $this;
            }
            $this->_last_refresh = microtime(true);
        }

        $primaryKey = $this->getPrimaryKey();
        $r = $this->newQuery()->select($fields)->where([$primaryKey => $this->$primaryKey])->execute();
        if (!$r) {
            throw new NotFoundException(static::class, [$primaryKey => $this->$primaryKey]);
        }

        $data = (array)$r[0];
        foreach ($this->getJsonFields() as $field) {
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

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize($this->toArray());
    }

    /**
     * @param string $serialized
     *
     * @return void
     */
    public function unserialize($serialized)
    {
        $unserialized = unserialize($serialized, ['allowed_classes' => false]);
        $this->_snapshot = $unserialized;

        foreach ((array)$unserialized as $field => $value) {
            $this->$field = $value;
        }
    }

    /**
     * @return array
     */
    public function relations()
    {
        return [];
    }

    /**
     * @param string $name
     * @param bool   $comment
     *
     * @return array
     */
    public static function constants($name, $comment = false)
    {
        $name = strtoupper($name) . '_';
        $constants = [];

        $rc = new ReflectionClass(static::class);
        $file = $comment ? file_get_contents($rc->getFileName()) : '';
        foreach ($rc->getConstants() as $cName => $cValue) {
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
    public function increment($field, $step = 1)
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
    public function decrement($field, $step = 1)
    {
        if (!$this->hasField($field)) {
            throw new InvalidArgumentException([':field field is invalid.', 'field' => $field]);
        }

        $this->$field = new Decrement($step);

        return $this;
    }

    /**
     * @param string $alias
     *
     * @return \ManaPHP\Data\QueryInterface <static>
     */
    public static function query($alias = null)
    {
        $query = static::sample()->newQuery();

        return $alias ? $query->from(static::class, $alias) : $query;
    }

    /**
     * @param int|string|array $filters =model_var(new static)
     *
     * @return \ManaPHP\Data\QueryInterface <static>
     */
    public static function where($filters)
    {
        $primaryKey = static::sample()->getPrimaryKey();

        return static::select()->where(is_scalar($filters) ? [$primaryKey => $filters] : $filters);
    }

    /**
     * @param array $filters =model_var(new static)
     *
     * @return \ManaPHP\Data\QueryInterface <static>
     */
    public static function search($filters)
    {
        return static::select()->search($filters);
    }

    /**
     * Deletes a model instance.
     *
     * @return static
     */
    public function delete()
    {
        $primaryKey = $this->getPrimaryKey();

        if ($this->$primaryKey === null) {
            throw new MisuseException('missing primary key value');
        }

        list($db, $table) = $this->getUniqueShard($this);

        $this->fireEvent('model:deleting');

        /** @var DbInterface $db */
        $db = $this->getShared($db);

        $db->delete($table, [$primaryKey => $this->$primaryKey]);

        $this->fireEvent('model:deleted');

        return $this;
    }

    /**
     * @param string $thatModel
     * @param string $thisField =model_field(new static)
     *
     * @return \ManaPHP\Data\Relation\BelongsTo
     */
    public function belongsTo($thatModel, $thisField = null)
    {
        /** @var \ManaPHP\Data\Model $thatModel */
        $that = $thatModel::sample();

        return new BelongsTo(static::class, $thisField ?? $that->getForeignedKey(), $thatModel, $that->getPrimaryKey());
    }

    /**
     * @param string $thatModel
     * @param string $thatField =model_field(new static)
     *
     * @return \ManaPHP\Data\Relation\HasOne
     */
    public function hasOne($thatModel, $thatField = null)
    {
        return new HasOne(static::class, $this->getPrimaryKey(), $thatModel, $thatField ?? $this->getForeignedKey());
    }

    /**
     * @param string $thatModel
     * @param string $thatField =model_field(new static)
     *
     * @return \ManaPHP\Data\Relation\HasMany
     */
    public function hasMany($thatModel, $thatField = null)
    {
        return new HasMany(static::class, $this->getPrimaryKey(), $thatModel, $thatField ?? $this->getForeignedKey());
    }

    /**
     * @param string $thatModel
     * @param string $pivotModel
     *
     * @return \ManaPHP\Data\Relation\HasManyToMany
     */
    public function hasManyToMany($thatModel, $pivotModel)
    {
        /** @var \ManaPHP\Data\Model $thatModel */
        $that = $thatModel::sample();

        return new HasManyToMany(
            static::class, $this->getPrimaryKey(), $thatModel, $that->getPrimaryKey(),
            $pivotModel, $this->getForeignedKey(), $that->getForeignedKey()
        );
    }

    /**
     * @param string $thatModel
     * @param string $thisFilter =model_field(new static)
     *
     * @return \ManaPHP\Data\Relation\HasManyOthers
     */
    public function hasManyOthers($thatModel, $thisFilter = null)
    {
        /** @var \ManaPHP\Data\Model $thatModel */
        $that = $thatModel::sample();

        $foreingedKey = $that->getForeignedKey();

        if ($thisFilter === null) {
            $keys = [];
            foreach ($this->getFields() as $field) {
                if ($field === $foreingedKey || $field === 'id' || $field === '_id' || !str_ends_with($field, '_id')) {
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
            static::class, $thisFilter, $that->getForeignedKey(), $thatModel, $that->getPrimaryKey()
        );
    }

    /**
     * alias of hasManyToMany
     *
     * @param string $thatModel
     * @param string $pivotModel
     *
     * @return \ManaPHP\Data\Relation\HasManyToMany
     */
    public function belongsToMany($thatModel, $pivotModel)
    {
        return $this->hasManyToMany($thatModel, $pivotModel);
    }

    /**
     * @param string $class
     * @param array  $params
     *
     * @return mixed
     */
    public function getInstance($class, $params = [])
    {
        return $this->_di->get($class, $params);
    }

    /**
     * @param string $name
     *
     * @return \ManaPHP\Data\Model|\ManaPHP\Data\Model[]|mixed
     * @throws \ManaPHP\Exception\UnknownPropertyException
     */
    public function __get($name)
    {
        if ($name === '_di') {
            return $this->_di = Di::getDefault();
        }

        /** @var \ManaPHP\Data\Relation\ManagerInterface $relationsManager */

        $method = 'get' . ucfirst($name);
        if (method_exists($this, $method)) {
            return $this->$name = $this->$method()->fetch();
        } elseif ($this->_di->has($name)) {
            return $this->{$name} = $this->getShared($name);
        } elseif (($relationsManager = $this->getShared('relationsManager'))->has($this, $name)) {
            return $this->$name = $relationsManager->lazyLoad($this, $name)->fetch();
        } else {
            throw new UnknownPropertyException(['`%s` does not contain `%s` field.`', static::class, $name]);
        }
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return void
     */
    public function __set($name, $value)
    {
        if (is_scalar($value)) {
            throw new MisuseException(['`%s` Model does\'t contains `%s` field', static::class, $name]);
        }

        $this->$name = $value;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->$name);
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return \ManaPHP\Data\QueryInterface
     * @throws \ManaPHP\Exception\NotSupportedException
     */
    public function __call($name, $arguments)
    {
        if (str_starts_with($name, 'get')) {
            /** @var \ManaPHP\Data\Relation\ManagerInterface $relationsManager */
            $relationsManager = $this->getShared('relationsManager');

            $relation = lcfirst(substr($name, 3));
            if ($relationsManager->has($this, $relation)) {
                return $relationsManager->lazyLoad($this, $relation);
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
            if (in_array($field, ['_di', '_snapshot', '_last_refresh'], true)) {
                continue;
            }

            if ($value instanceof Component && !$value instanceof self) {
                continue;
            }

            $data[$field] = $value;
        }

        if ($changedFields = $this->getChangedFields()) {
            $data['*changed_fields*'] = $changedFields;
        }

        foreach ($this->getFields() as $field) {
            if (!isset($this->$field)) {
                continue;
            }

            $value = $this->$field;

            if (is_int($value) && $value > 100000000 /**1973/3/3 17:46:40*/ && !str_ends_with($field, '_id')) {
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

    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    public function offsetGet($offset)
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