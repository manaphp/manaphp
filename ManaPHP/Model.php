<?php
namespace ManaPHP;

use ManaPHP\Exception\BadMethodCallException;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\InvalidJsonException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\MissingFieldException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Exception\ParameterOrderException;
use ManaPHP\Exception\PreconditionException;
use ManaPHP\Exception\UnknownPropertyException;
use ManaPHP\Model\Expression\Increment;
use ManaPHP\Model\NotFoundException;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Model
 *
 * @package ManaPHP
 *
 * @property-read \ManaPHP\Model\ValidatorInterface $modelsValidator
 * @property-read \ManaPHP\Http\RequestInterface    $request
 */
abstract class Model extends Component implements ModelInterface, \Serializable, \ArrayAccess
{
    const OP_NONE = 0;
    const OP_CREATE = 1;
    const OP_READ = 2;
    const OP_UPDATE = 3;
    const OP_DELETE = 4;

    /**
     * @var array
     */
    protected $_snapshot = [];

    /**
     * @var float
     */
    protected $_last_refresh = 0;

    /**
     * \ManaPHP\Model constructor
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->_di = Di::getDefault();

        if ($data) {
            foreach ($this->getJsonFields() as $field) {
                if (isset($data[$field]) && is_string($data[$field])) {
                    if ($data[$field] === '') {
                        $data[$field] = [];
                    } elseif (($json = json_decode($data[$field], true)) === null) {
                        throw new InvalidJsonException(['`:field` field value of `:model` is not a valid json string',
                            'field' => $field,
                            'model' => static::class]);
                    } else {
                        $data[$field] = $json;
                    }
                }
            }

            if ($this->_snapshot !== false) {
                $this->_snapshot = $data;
            }
            foreach ($data as $field => $value) {
                $this->{$field} = $value;
            }

            if (method_exists($this, 'afterFetch')) {
                $this->afterFetch();
            }
        }
    }

    /**
     * @return array
     */
    public function getForeignKeys()
    {
        $primaryKey = $this->getPrimaryKey();

        $keys = [];
        foreach ($this->getFields() as $field) {
            if ($field === $primaryKey) {
                continue;
            }

            if (($pos = strpos($field, '_id', 1)) && $pos + 3 === strlen($field)) {
                $keys[] = $field;
            }
        }

        return $keys;
    }

    /**
     * Returns table name mapped in the model
     *
     * @param mixed $context
     *
     * @return string
     */
    public function getSource($context = null)
    {
        $class = static::class;
        return Text::underscore(($pos = strrpos($class, '\\')) === false ? $class : substr($class, $pos + 1));
    }

    /**
     * @return string|null
     */
    public function getAutoIncrementField()
    {
        $primaryKey = $this->getPrimaryKey();
        return is_string($primaryKey) ? $primaryKey : null;
    }

    /**
     * @param string $field
     *
     * @return bool
     */
    public function hasField($field)
    {
        return in_array($field, $this->getFields(), true);
    }

    /**
     * @param string $field
     *
     * @return string
     */
    public function getDateFormat($field)
    {
        if ($this->_snapshot && isset($this->_snapshot[$field])) {
            $ts = is_numeric($this->_snapshot[$field]);
        } elseif (isset($this->$field)) {
            $ts = is_numeric($this->$field);
        } else {
            $ts = in_array($field, $this->getIntFields(), true);
        }
        return $ts ? 'U' : 'Y-m-d H:i:s';
    }

    /**
     * @return array|null
     */
    public function getSafeFields()
    {
        return array_keys($this->rules()) ?: null;
    }

    /**
     * @return array
     */
    public function getJsonFields()
    {
        return [];
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [];
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param array $filters
     * @param array $options
     * @param array $fields
     *
     * @return  static[]
     */
    public static function all($filters = [], $options = null, $fields = null)
    {
        return static::query()->select($fields)->where($filters)->options($options)->fetch();
    }

    /**
     * @param string|array $field
     * @param array        $filters
     *
     * @return array
     */
    public static function lists($field, $filters = null)
    {
        $model = new static;

        $query = static::query(null, $model)->where($filters);

        $list = [];

        if (is_string($field)) {
            $keyField = $model->getPrimaryKey();
            $valueField = $field;

            $query = $query->select([$keyField, $valueField]);
            if (in_array('display_order', $model->getFields(), true)) {
                return $query->orderBy(['display_order' => SORT_DESC, $keyField => SORT_ASC])->fetch(true);
            } else {
                return $query->orderBy($keyField)->fetch(true);
            }
        } else {
            $keyField = key($field);
            $valueField = current($field);
            /** @noinspection ForeachSourceInspection */
            foreach ($query->select([$keyField, $valueField])->fetch() as $v) {
                $keyValue = $v->{$keyField};

                if (!isset($list[$keyValue])) {
                    $list[$keyValue] = $v->{$valueField};
                } elseif (is_array($list[$keyValue])) {
                    $list[$keyValue][] = $v->{$valueField};
                } else {
                    $list[$keyValue] = [$list[$keyValue], $v->{$valueField}];
                }
            }

            return $list;
        }
    }

    /**
     * @param int|string $id
     * @param int|array  $fieldsOrTtl
     *
     * @return static
     */
    public static function get($id, $fieldsOrTtl = null)
    {
        if (!is_scalar($id)) {
            throw new InvalidValueException('Model::get id is not scalar');
        }

        $model = new static;

        if (!is_int($fieldsOrTtl)) {
            if (!$rs = static::query(null, $model)->select($fieldsOrTtl)->whereEq($model->getPrimaryKey(), $id)->limit(1)->fetch()) {
                throw new NotFoundException(['No record for `:model` model of `:id` id', 'model' => static::class, 'id' => $id]);
            } else {
                return $rs[0];
            }
        }

        $ttl = $fieldsOrTtl;

        $key = '_mp:models:get:' . $model->getSource() . ":$id:$ttl";
        if ($r = $model->_di->ipcCache->get($key)) {
            /** @noinspection PhpIncompatibleReturnTypeInspection */
            return $r;
        }

        if (!$r) {
            if (!$rs = static::query(null, $model)->whereEq($model->getPrimaryKey(), $id)->limit(1)->fetch()) {
                throw new NotFoundException(['No record for `:model` model of `:id` id', 'model' => static::class, 'id' => $id]);
            }

            $r = $rs[0];
            $r->_snapshot = false;

            $model->_di->ipcCache->set($key, $r, $ttl);
        }

        return $r;
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param int|string|array $filters
     * @param array            $fields
     *
     * @return static|null
     */
    public static function first($filters, $fields = null)
    {
        if ($filters === null) {
            throw new MisuseException('Model:first is not support null value filters');
        }

        $model = new static;
        $query = static::query(null, $model)->select($fields ?: null)->limit(1);

        if (is_scalar($filters)) {
            $query->whereEq($model->getPrimaryKey(), $filters);
        } else {
            $query->where($filters);
        }

        $rs = $query->fetch();
        return isset($rs[0]) ? $rs[0] : null;
    }

    /**
     * @param int|string|array $filters
     * @param array            $fields
     *
     * @return static
     */
    public static function firstOrFail($filters, $fields = null)
    {
        if (!$r = static::first($filters, $fields)) {
            $exception = new NotFoundException([
                'No record for `:model` model with `:query` query',
                'model' => static::class,
                'query' => json_encode($filters, JSON_UNESCAPED_SLASHES, JSON_UNESCAPED_UNICODE)
            ]);
            $exception->model = static::class;
            $exception->filters = $filters;

            throw $exception;
        }

        return $r;
    }

    /**
     * @param array $fields
     *
     * @return static|null
     */
    public static function firstOrNull($fields = null)
    {
        static $request;
        if (!$request) {
            $request = Di::getDefault()->getShared('request');
        }

        if (!$request->isAjax()) {
            return null;
        }

        $model = new static();
        return static::get($request->getId($model->getPrimaryKey()));
    }

    /**
     * Allows to query the last record that match the specified conditions
     *
     * @param array $filters
     * @param array $fields
     *
     * @return static|null
     */
    public static function last($filters = null, $fields = null)
    {
        $model = new static();

        if (is_string($primaryKey = $model->getPrimaryKey())) {
            $options['order'] = [$primaryKey => SORT_DESC];
        } else {
            throw new BadMethodCallException('infer `:class` order condition for last failed:', ['class' => static::class]);
        }

        $rs = static::query(null, $model)->select($fields)->where($filters)->limit(1)->fetch();
        return isset($rs[0]) ? $rs[0] : null;
    }

    /**
     * @param int|string|array $filters
     * @param string           $field
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

        $model = new static;
        $pkName = $model->getPrimaryKey();

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
            $rs = static::query(null, $model)->select([$field])->where($filters)->limit(1)->fetch(true);
            return $rs ? $rs[0][$field] : null;
        }

        $key = '_mp:models:value:' . $model->getSource() . ":$field:$pkValue:$ttl";
        if (($value = $model->_di->ipcCache->get($key)) !== false) {
            return $value;
        }

        $rs = static::query(null, $model)->select([$field])->whereEq($pkName, $pkValue)->limit(1)->fetch(true);
        $value = $rs ? $rs[0][$field] : null;

        $model->_di->ipcCache->set($key, $value, $ttl);

        return $value;
    }

    /**
     * @param int|string|array $filters
     * @param string           $field
     * @param int              $ttl
     *
     * @return int|float|string
     */
    public static function valueOrFail($filters, $field, $ttl = null)
    {
        $value = static::value($filters, $field, $ttl);
        if ($value === null) {
            throw new NotFoundException(['valueOrFail: `:model` model with `:query` query record is not exists',
                'model' => static::class,
                'query' => json_encode($filters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        } else {
            return $value;
        }
    }

    /**
     * @param string $field
     * @param array  $filters
     *
     * @return array
     */
    public static function values($field, $filters = null)
    {
        if (!is_string($field)) {
            throw new ParameterOrderException(__METHOD__ . ' field');
        }
        return static::query()->where($filters)->values($field);
    }

    /**
     * @param int|string|array $filters
     *
     * @return bool
     */
    public static function exists($filters)
    {
        if (is_scalar($filters)) {
            $model = new static;
            return static::query(null, $model)->whereEq($model->getPrimaryKey(), $filters)->exists();
        } else {
            return static::query()->where($filters)->exists();
        }
    }

    /**
     * @param array        $filters
     * @param array        $aggregation
     * @param string|array $options
     *
     * @return array
     */
    public static function aggregate($filters, $aggregation, $options = null)
    {
        if (is_string($options)) {
            $options = ['group' => $options];
        }
        return static::query()->where($filters)->options($options)->aggregate($aggregation);
    }

    /**
     * Allows to count how many records match the specified conditions
     *
     * @param array  $filters
     * @param string $field
     *
     * @return int
     */
    public static function count($filters = null, $field = '*')
    {
        return static::query()->where($filters)->count($field);
    }

    /**
     * Allows to calculate a summary on a field that match the specified conditions
     *
     * @param string $field
     * @param array  $filters
     *
     * @return int|float|null
     */
    public static function sum($field, $filters = null)
    {
        return static::query()->where($filters)->sum($field);
    }

    /**
     * Allows to get the max value of a column that match the specified conditions
     *
     * @param string $field
     * @param array  $filters
     *
     * @return int|float|null
     */
    public static function max($field, $filters = null)
    {
        return static::query()->where($filters)->max($field);
    }

    /**
     * Allows to get the min value of a column that match the specified conditions
     *
     *
     * @param string $field
     * @param array  $filters
     *
     * @return int|float|null
     */
    public static function min($field, $filters = null)
    {
        return static::query()->where($filters)->min($field);
    }

    /**
     * Allows to calculate the average value on a column matching the specified conditions
     *
     * @param string $field
     * @param array  $filters
     *
     * @return float|null
     */
    public static function avg($field, $filters = null)
    {
        return (float)static::query()->where($filters)->avg($field);
    }

    /**
     * Assigns values to a model from an array
     *
     * @param array $data
     * @param array $whiteList
     *
     * @return static
     */
    public function assign($data, $whiteList = null)
    {
        if ($whiteList === null) {
            $whiteList = $this->getSafeFields();
        }

        if ($whiteList === null) {
            throw new PreconditionException(['`:model` model do not define accessible fields.', 'model' => static::class]);
        }

        foreach ($whiteList ?: $this->getFields() as $field) {
            if (isset($data[$field])) {
                $value = $data[$field];
                $this->{$field} = is_string($value) ? trim($value) : $value;
            }
        }

        return $this;
    }

    /**
     * @param string|array $fields
     *
     * @return void
     */
    public function validate($fields = null)
    {
        $this->_di->modelsValidator->validate($this, $fields ?: $this->getChangedFields());
    }

    /**
     * @param int $opMode
     *
     * @return  array
     */
    public function getAutoFilledData($opMode)
    {
        $data = [];
        if ($opMode === self::OP_CREATE) {
            $data['updated_time'] = $data['created_time'] = date($this->getDateFormat('created_time'));
            $data['updated_at'] = $data['created_at'] = date($this->getDateFormat('created_at'));
            $data['creator_id'] = $data['updator_id'] = $this->_di->identity->getId(0);
            $data['creator_name'] = $data['updator_name'] = $this->_di->identity->getName('');
        } elseif ($opMode === self::OP_UPDATE) {
            $data['updated_time'] = date($this->getDateFormat('updated_time'));
            $data['updated_at'] = date($this->getDateFormat('updated_at'));
            $data['updator_name'] = $this->_di->identity->getName('');
        }

        return $data;
    }

    /**
     * @param array $data
     *
     * @return static|null
     */
    public static function createOrNull($data = null)
    {
        static $request;
        if (!$request) {
            $request = Di::getDefault()->getShared('request');
        }

        if (!$request->isPost()) {
            return null;
        }

        $instance = new static();

        $_request = $request->get();

        if ($data === null) {
            foreach ($instance->getSafeFields() as $field) {
                if (isset($_request[$field])) {
                    $instance->$field = $_request[$field];
                }
            }
        } else {
            foreach ($data as $k => $v) {
                if (is_int($k)) {
                    $field = $v;
                    if (!isset($_request[$field])) {
                        throw new MissingFieldException($field);
                    }
                    $instance->$field = $_request[$field];
                } else {
                    $instance->$k = $v;
                }
            }
        }

        return $instance->create();
    }

    /**
     * @param array $data
     *
     * @return static|null
     */
    public static function updateOrNull($data = null)
    {
        static $request;
        if (!$request) {
            $request = Di::getDefault()->getShared('request');
        }

        if (!$request->isPost() && !$request->isPut() && !$request->isPatch()) {
            return null;
        }

        $model = new static;

        $pkName = $model->getPrimaryKey();

        $_request = $request->get();
        if ($data === null) {
            $instance = static::get($request->getId($pkName));

            foreach ($model->getSafeFields() as $field) {
                if (isset($_request[$field])) {
                    $instance->$field = $_request[$field];
                }
            }
        } else {
            $instance = static::get(isset($data[$pkName]) ? $data[$pkName] : $request->getId($pkName));

            foreach ($data as $k => $v) {
                if (is_int($k)) {
                    $field = $v;
                    if (!isset($_request[$field])) {
                        throw new MissingFieldException($field);
                    }
                    $instance->$field = $_request[$field];
                } else {
                    $instance->$k = $v;
                }
            }
        }

        return $instance;
    }

    /**
     * Checks if the current record already exists or not
     *
     * @return bool
     */
    protected function _exists()
    {
        return static::query(null, $this)->where($this->_getPrimaryKeyValuePairs())->forceUseMaster()->exists();
    }

    /**
     * Inserts or updates a model instance. Returning true on success or false otherwise.
     *
     * @return static
     */
    public function save()
    {
        if ($this->_exists()) {
            return $this->update();
        } else {
            return $this->create();
        }
    }

    /**
     * @return array
     */
    protected function _getPrimaryKeyValuePairs()
    {
        $primaryKey = $this->getPrimaryKey();
        if (is_string($primaryKey)) {
            if (!isset($this->{$primaryKey})) {
                throw new PreconditionException(['`:model` model cannot be updated because primary key value is not provided', 'model' => static::class]);
            }
            return [$primaryKey => $this->$primaryKey];
        } elseif (is_array($primaryKey)) {
            $keyValue = [];
            foreach ($primaryKey as $key) {
                if (!isset($this->$key)) {
                    throw new PreconditionException(['`:1` model cannot be updated because some primary key value is not provided', static::class]);
                }
                $keyValue[$key] = $this->$key;
            }
            return $keyValue;
        } else {
            throw new NotSupportedException(['`:model` model does not has primary key', 'model' => static::class]);
        }
    }

    /**
     * Deletes a model instance. Returning true on success or false otherwise.
     *
     * @return static
     */
    public function delete()
    {
        $this->eventsManager->fireEvent('model:beforeDelete', $this);

        static::query(null, $this)->where($this->_getPrimaryKeyValuePairs())->delete();

        $this->eventsManager->fireEvent('model:afterDelete', $this);

        return $this;
    }

    /**
     * @return static|null
     */
    public static function deleteOrNull()
    {
        static $request;
        if (!$request) {
            $request = Di::getDefault()->getShared('request');
        }

        if (!$request->isDelete() && !$request->isPost()) {
            return null;
        }

        $model = new static();
        $pkName = $model->getPrimaryKey();

        return static::get($request->getId($pkName))->delete();
    }

    /**
     * @param array $fieldValues
     * @param array $filters
     *
     * @return int
     */
    public static function updateAll($fieldValues, $filters)
    {
        return static::query()->where($filters)->update($fieldValues);
    }

    /**
     * @param int|string $primaryKey
     * @param array      $fieldValues
     *
     * @return int
     */
    public static function updateRecord($primaryKey, $fieldValues)
    {
        if (!is_scalar($primaryKey)) {
            throw new InvalidArgumentException(['`:value` is not a valid primary key value', 'value' => $primaryKey]);
        }

        $instance = new static();
        return static::query()->whereEq($instance->getPrimaryKey(), $primaryKey)->update($fieldValues);
    }

    /**
     * @param array $filters
     *
     * @return int
     */
    public static function deleteAll($filters)
    {
        return static::query()->where($filters)->delete();
    }

    /**
     * Returns the instance as an array representation
     *
     * @return array
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
     * Returns the internal snapshot data
     *
     * @return array
     */
    public function getSnapshotData()
    {
        if ($this->_snapshot === false) {
            throw new PreconditionException(['getSnapshotData failed: `:model` instance is snapshot disabled', 'model' => static::class]);
        }

        return $this->_snapshot;
    }

    /**
     * @return static
     */
    public function disableSnapshot()
    {
        $this->_snapshot = false;

        return $this;
    }

    /**
     * Returns a list of changed values
     *
     * @return array
     */
    public function getChangedFields()
    {
        if ($this->_snapshot === false) {
            throw new PreconditionException(['getChangedFields failed: `:model` instance is snapshot disabled', 'model' => static::class]);
        }

        $changed = [];

        foreach ($this->getFields() as $field) {
            if (isset($this->_snapshot[$field])) {
                if ($this->{$field} !== $this->_snapshot[$field]) {
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
     * @param string|array $fields
     *
     * @return bool
     */
    public function hasChanged($fields)
    {
        if ($this->_snapshot === false) {
            throw new PreconditionException(['getChangedFields failed: `:model` instance is snapshot disabled', 'model' => static::class]);
        }

        /** @noinspection ForeachSourceInspection */
        foreach ((array)$fields as $field) {
            if (!isset($this->_snapshot[$field]) || $this->{$field} !== $this->_snapshot[$field]) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param float $interval
     * @param array $fields
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

        $r = static::query(null, $this)->select($fields)->where($this->_getPrimaryKeyValuePairs())->fetch(true);
        if (!$r) {
            throw new NotFoundException(['`:1` model refresh failed: `:2` record is not exists now! ',
                static::class,
                json_encode($this->_getPrimaryKeyValuePairs())]);
        }

        $data = (array)$r[0];
        foreach ($this->getJsonFields() as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                if ($data[$field] === '') {
                    $data[$field] = [];
                } elseif (($json = json_decode($data[$field], true)) === null) {
                    throw new InvalidJsonException(['`:field` field value of `:model` is not a valid json string',
                        'field' => $field,
                        'model' => static::class]);
                } else {
                    $data[$field] = $json;
                }
            }
        }

        foreach ($data as $field => $value) {
            $this->$field = $value;
        }

        if ($this->_snapshot !== false) {
            $this->_snapshot = array_merge($this->_snapshot, $data);
        }

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
        $unserialized = unserialize($serialized);
        if ($this->_snapshot !== false) {
            $this->_snapshot = $unserialized;
        }

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
     *
     * @return array
     * @throws \ReflectionException
     */
    public static function consts($name)
    {
        $name = strtoupper($name) . '_';
        $constants = [];
        foreach ((new \ReflectionClass(static::class))->getConstants() as $cName => $cValue) {
            if (strpos($cName, $name) === 0) {
                $constants[$cValue] = strtolower(substr($cName, strlen($name)));
            }
        }

        if (!$constants) {
            throw new MisuseException(['starts with `:1` constants is not exists in `:2` model', $name, static::class]);
        }

        return $constants;
    }

    /**
     * @param string    $field
     * @param int|float $step
     *
     * @return static
     */
    public function increment($field, $step = 1)
    {
        if (!in_array($field, $this->getFields(), true)) {
            throw new InvalidArgumentException([':field field is invalid.', 'field' => $field]);
        }

        $this->$field = new Increment($step);

        return $this;
    }

    /**
     * @param string    $field
     * @param int|float $step
     *
     * @return static
     */
    public function decrement($field, $step = 1)
    {
        return $this->increment($field, -$step);
    }

    /**
     * @param string $name
     *
     * @return \ManaPHP\Model|\ManaPHP\Model[]|mixed
     * @throws \ManaPHP\Exception\UnknownPropertyException
     */
    public function __get($name)
    {
        $method = 'get' . ucfirst($name);
        if (method_exists($this, $method)) {
            return $this->$name = $this->$method()->fetch();
        } elseif ($this->_di->has($name)) {
            return $this->{$name} = $this->_di->getShared($name);
        } elseif ($this->_di->relationsManager->has($this, $name)) {
            return $this->$name = $this->_di->relationsManager->lazyLoad($this, $name)->fetch();
        } else {
            throw new UnknownPropertyException(['`:class` does not contain `:field` field: `:fields`',
                'class' => static::class,
                'field' => $name,
                'fields' => implode(',', $this->getFields())]);
        }
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @return \ManaPHP\QueryInterface
     * @throws \ManaPHP\Exception\BadMethodCallException
     * @throws \ManaPHP\Exception\NotSupportedException
     */
    public function __call($name, $arguments)
    {
        if (strpos($name, 'get') === 0) {
            $relation = lcfirst(substr($name, 3));
            if ($this->_di->relationsManager->has($this, $relation)) {
                return $this->_di->relationsManager->lazyLoad($this, $relation);
            } else {
                throw new NotSupportedException(['`:class` model does not define `:method` relation', 'class' => static::class, 'method' => $relation]);
            }
        }
        throw new BadMethodCallException(['`:class` does not contain `:method` method', 'class' => static::class, 'method' => $name]);
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        $data = [];

        foreach (get_object_vars($this) as $field => $value) {
            if (in_array($field, ['_di', '_snapshot', '_last_refresh', 'eventsManager'], true)) {
                continue;
            }

            if ($value instanceof Component && !$value instanceof self) {
                continue;
            }

            $data[$field] = $value;
        }

        if ($this->_snapshot && $changedFields = $this->getChangedFields()) {
            $data['*changed_fields*'] = $changedFields;
        }

        foreach ($this->getFields() as $field) {
            if (!isset($this->$field)) {
                continue;
            }

            $value = $this->$field;

            if (is_int($value) && $value > 100000000 /**1973/3/3 17:46:40*/ && strpos($field, '_id') === false) {
                $data['*human_time*'][$field] = date('Y-m-d H:i:s', $value);
            }

            if (is_numeric($value)) {
                foreach ((new \ReflectionClass(static::class))->getConstants() as $cName => $cValue) {
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

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}