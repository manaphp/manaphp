<?php
namespace ManaPHP;

use ManaPHP\Exception\BadMethodCallException;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\InvalidJsonException;
use ManaPHP\Exception\InvalidValueException;
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
 *
 * method beforeCreate()
 * method afterCreate()
 *
 * method beforeSave()
 * method afterSave()
 *
 * method afterFetch()
 *
 * method beforeUpdate()
 * method afterUpdate()
 *
 * method beforeDelete()
 * method afterDelete()
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
                            'model' => get_class($this)]);
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
        $modelName = get_called_class();
        return Text::underscore(($pos = strrpos($modelName, '\\')) === false ? $modelName : substr($modelName, $pos + 1));
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
     * @return string|null
     */
    public function getDisplayField()
    {
        $fields = $this->getFields();

        if (in_array('name', $fields, true)) {
            return 'name';
        }

        $primaryKey = $this->getPrimaryKey();
        if (preg_match('#^(.*)_id$#', $primaryKey, $match)) {
            $tryField = $match[1] . '_name';
            if (in_array($tryField, $fields, true)) {
                return $tryField;
            }
        }

        return null;
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
     * @param array $filters
     * @param array $options
     * @param array $fields
     *
     * @return  \ManaPHP\Paginator
     */
    public static function paginate($filters = [], $options = null, $fields = null)
    {
        return static::query()->select($fields)->where($filters)->options($options)
            ->paginate(isset($options['size']) ? $options['size'] : null, isset($options['page']) ? $options['page'] : null);
    }

    /**
     * @param array $filters
     * @param array $options
     * @param array $fields
     *
     * @return  \ManaPHP\Paginator
     */
    public static function search($filters = [], $options = null, $fields = null)
    {
        return static::query()->select($fields)->whereSearch($filters)->options($options)
            ->paginate(isset($options['size']) ? $options['size'] : null, isset($options['page']) ? $options['page'] : null);
    }

    /**
     * @param array        $filters
     * @param string|array $field
     *
     * @return array
     */
    public static function lists($filters = [], $field = null)
    {
        $model = new static;

        $query = static::query(null, $model)->where($filters);

        $list = [];
        if ($field === null && !$field = $model->getDisplayField()) {
            throw new PreconditionException(['invoke :model:findList method must provide displayField', 'model' => get_called_class()]);
        }

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

    public function getCacheCapacity()
    {
        return 100;
    }

    /**
     * @param int|string $id
     * @param int|array  $fieldsOrTtl
     *
     * @return static
     */
    public static function get($id = null, $fieldsOrTtl = null)
    {
        if (is_int($fieldsOrTtl)) {
            $ttl = $fieldsOrTtl;
            $fields = null;
        } else {
            $ttl = null;
            $fields = $fieldsOrTtl;
        }

        $model = new static;

        $pkName = $model->getPrimaryKey();

        if ($id === null) {
            $di = $model->_di;
            if ($di->request->has($pkName)) {
                $id = $di->request->get($pkName);
            } elseif ($di->dispatcher->hasParam($pkName)) {
                $id = $di->dispatcher->getParam($pkName);
            } elseif (count($params = $di->dispatcher->getParams()) === 1 && isset($params[0])) {
                $id = $params[0];
            } else {
                throw new InvalidArgumentException(['missing condition for `:class::get` method', 'class' => get_called_class()]);
            }
        }

        if (!is_scalar($id)) {
            throw new InvalidValueException('Model::get id is not scalar');
        }

        if (!$ttl) {
            if (!$rs = static::query(null, $model)->select($fields)->whereEq($pkName, $id)->limit(1)->fetch()) {
                throw new NotFoundException(['No record for `:model` model of `:id` id', 'model' => get_called_class(), 'id' => $id]);
            } else {
                return $rs[0];
            }
        }

        static $cached = [];

        $current = microtime(true);
        $className = get_class($model);
        if (isset($cached[$className][$id])) {
            $cache = $cached[$className][$id];
            if ($ttl === -1 || $current - $cache[0] <= $ttl) {
                return $cache[1];
            } else {
                unset($cached[$className][$id]);
            }
        }

        $r = null;
        $key = '_mp:models:get:' . $model->getSource() . ":$id";
        if ($cache = $model->_di->ipcCache->get($key)) {
            /** @noinspection NestedPositiveIfStatementsInspection */
            if ($ttl === -1 || $current - $cache[0] <= $ttl) {
                $current = $cache[0];
                $r = $cache[1];
            }
        }

        if (!$r) {
            if (!$rs = static::query(null, $model)->select($fields)->whereEq($pkName, $id)->limit(1)->fetch()) {
                throw new NotFoundException(['No record for `:model` model of `:id` id', 'model' => get_called_class(), 'id' => $id]);
            }

            $r = $rs[0];
            /**
             * @var \ManaPHP\Model $r
             */
            $r->_snapshot = false;

            $model->_di->ipcCache->set($key, [$current, $r], $ttl !== -1 ? $ttl : mt_rand(3000, 3600));
        }

        $cached[$className][$id] = [$current, $r];
        /** @noinspection PhpUndefinedVariableInspection */
        if (count($cached[$className]) > $model->getCacheCapacity()) {
            unset($cached[$className][key($cached[$className])]);
        }

        return $r;
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param int|string|array $filters
     * @param array            $fields
     * @param array            $options
     *
     * @return static|null
     */
    public static function first($filters = null, $fields = null, $options = null)
    {
        $model = new static;

        if ($filters === null) {
            $di = $model->_di;
            $pkName = $model->getPrimaryKey();

            if ($di->request->has($pkName)) {
                $pkValue = $di->request->get($pkName);
            } elseif ($di->dispatcher->hasParam($pkName)) {
                $pkValue = $di->dispatcher->getParam($pkName);
            } elseif (count($params = $di->dispatcher->getParams()) === 1 && isset($params[0])) {
                $pkValue = $params[0];
            } else {
                throw new InvalidArgumentException('missing filters condition for Model::first method');
            }

            if (!is_scalar($pkValue)) {
                throw new InvalidValueException('Model::first primary key value is not scalar');
            }
            $filters = [$pkName => $pkValue];
        } elseif (is_scalar($filters)) {
            $filters = [$model->getPrimaryKey() => $filters];
        }

        $rs = static::query(null, $model)->select($fields ?: null)->where($filters)->options($options)->limit(1)->fetch();
        return isset($rs[0]) ? $rs[0] : null;
    }

    /**
     * @param int|string|array $filters
     * @param array            $fields
     * @param array            $options
     *
     * @return static
     */
    public static function firstOrFail($filters = null, $fields = null, $options = null)
    {
        if (!$r = static::first($filters, $fields, $options)) {
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
     * Allows to query the last record that match the specified conditions
     *
     * @param array $filters
     * @param array $fields
     * @param array $options
     *
     * @return static|null
     */
    public static function last($filters = null, $fields = null, $options = null)
    {
        $model = new static();
        if ($options === null) {
            $options = [];
        }

        if (!isset($options['order'])) {
            if (is_string($primaryKey = $model->getPrimaryKey())) {
                $options['order'] = [$primaryKey => SORT_DESC];
            } else {
                throw new BadMethodCallException('infer `:class` order condition for last failed:', ['class' => get_called_class()]);
            }
        }

        $rs = static::query(null, $model)->select($fields)->where($filters)->options($options)->limit(1)->fetch();
        return isset($rs[0]) ? $rs[0] : null;
    }

    /**
     * @param int|string|array $filters
     * @param string           $field
     * @param int              $ttl
     *
     * @return int|double|string|null
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

        static $cached = [];

        $current = microtime(true);
        $className = get_called_class();

        if (isset($cached[$className][$field][$pkValue])) {
            $cache = $cached[$className][$field][$pkValue];
            if ($ttl === -1 || $current - $cache[0] <= $ttl) {
                return $cache[1];
            }
            unset($cached[$className][$field][$pkValue]);
        }

        $value = null;
        $key = '_mp:models:value:' . $model->getSource() . ":$field:$pkValue";
        if ($cache = $model->_di->ipcCache->get($key)) {
            /** @noinspection NestedPositiveIfStatementsInspection */
            if ($ttl === -1 || $current - $cache[0] <= $ttl) {
                $current = $cache[0];
                $value = $cache[1];
            }
        }

        if ($value === null) {
            $rs = static::query(null, $model)->select([$field])->whereEq($pkName, $pkValue)->limit(1)->fetch(true);
            $value = $rs ? $rs[0][$field] : null;

            $model->_di->ipcCache->set($key, [$current, $value], $ttl !== -1 ? $ttl : mt_rand(3000, 3600));
        }

        $cached[$className][$field][$pkValue] = [$current, $value];
        if (count($cached[$className][$field]) > $model->getCacheCapacity()) {
            unset($cached[$className][$field][key($cached[$className][$field])]);
        }

        return $value;
    }

    /**
     * @param int|string|array $filters
     * @param string           $field
     * @param int              $ttl
     *
     * @return int|double|string
     */
    public static function valueOrFail($filters, $field, $ttl = null)
    {
        $value = static::value($filters, $field, $ttl);
        if ($value === null) {
            throw new NotFoundException(['valueOrFail: `:model` model with `:query` query record is not exists',
                'model' => get_called_class(),
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
     * @param string $field
     * @param array  $filters
     *
     * @return array
     */
    public static function kvalues($field = null, $filters = null)
    {
        $model = new static();
        if ($field === null && !$field = $model->getDisplayField()) {
            throw new PreconditionException(['invoke :model:kvalues method must provide displayField', 'model' => get_called_class()]);
        }

        $pkField = $model->getPrimaryKey();

        $query = static::query(null, $model)->select([$pkField, $field])->where($filters)->indexBy([$pkField => $field]);
        if (in_array('display_order', $model->getFields(), true)) {
            $query->orderBy(['display_order' => SORT_DESC, $pkField => SORT_ASC]);
        }

        return $query->fetch(true);
    }

    /**
     * @param array $filters
     *
     * @return mixed
     */
    public static function vlabels($filters = null)
    {
        $model = new static();
        if (!$field = $model->getDisplayField()) {
            throw new PreconditionException(['invoke :model:vlabels method must provide displayField', 'model' => get_called_class()]);
        }

        $pkField = $model->getPrimaryKey();

        $query = static::query(null, $model)->select(['value' => $pkField, 'label' => $field])->where($filters);
        if (in_array('display_order', $model->getFields(), true)) {
            $query->orderBy(['display_order' => SORT_DESC, $pkField => SORT_ASC]);
        }

        return $query->fetch(true);
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
     * @return double|null
     */
    public static function avg($field, $filters = null)
    {
        return (double)static::query()->where($filters)->avg($field);
    }

    /**
     * Fires an event, implicitly calls behaviors and listeners in the events manager are notified
     *
     * @param string $eventName
     *
     * @return void
     */
    protected function _fireEvent($eventName)
    {
        if (method_exists($this, $eventName)) {
            $this->{$eventName}();
        }

        $this->fireEvent('model:' . $eventName);
    }

    /**
     * Fires an internal event that cancels the operation
     *
     * @param string $eventName
     *
     * @return bool
     */
    protected function _fireEventCancel($eventName)
    {
        if (method_exists($this, $eventName) && $this->{$eventName}() === false) {
            return false;
        }

        return $this->fireEvent('model:' . $eventName) !== false;
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
            throw new PreconditionException(['`:model` model do not define accessible fields.', 'model' => get_called_class()]);
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
     * @param array $whiteList
     *
     * @return static
     */
    public static function createOrFail($data = null, $whiteList = null)
    {
        $instance = static::newOrFail($data, $whiteList);
        $instance->create();

        return $instance;
    }

    /**
     * @param array $data
     * @param array $whiteList
     *
     * @return static
     */
    public static function newOrFail($data = null, $whiteList = null)
    {
        $model = new static();

        if ($data === null) {
            $data = $model->_di->request->get();
        }

        unset($data[$model->getPrimaryKey()]);

        $model->assign($data, $whiteList);

        return $model;
    }

    /**
     * @param array $data
     * @param array $whiteList
     *
     * @return static
     */
    public static function updateOrFail($data = null, $whiteList = null)
    {
        $model = new static;
        $di = $model->_di;

        if ($data === null) {
            $data = $di->request->get();
        } else {
            $data += $di->request->get();
        }

        $pkName = $model->getPrimaryKey();

        if (isset($data[$pkName])) {
            $pkValue = $data[$pkName];
            unset($data[$pkName]);
        } elseif ($di->request->has($pkName)) {
            $pkValue = $di->request->get($pkName);
        } elseif ($di->dispatcher->hasParam($pkName)) {
            $pkValue = $di->dispatcher->getParam($pkName);
        } elseif (count($params = $di->dispatcher->getParams()) === 1 && isset($params[0])) {
            $pkValue = $params[0];
        } else {
            throw new PreconditionException('missing primary key value');
        }

        if (!is_scalar($pkValue)) {
            throw new InvalidValueException('primary key value is not scalar');
        }

        $instance = static::firstOrFail([$pkName => $pkValue]);
        $instance->assign($data, $whiteList);
        $instance->update();

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
     * @param array $data
     * @param array $whiteList
     *
     * @return static
     */
    public static function saveOrFail($data = null, $whiteList = null)
    {
        $model = new static;
        $di = $model->_di;

        if ($data === null) {
            $data = $di->request->get();
        }

        $pkName = $model->getPrimaryKey();

        $pkValue = null;

        if (isset($data[$pkName])) {
            $pkValue = $data[$pkName];
        } elseif ($di->dispatcher->hasParam($pkName)) {
            $pkValue = $di->dispatcher->getParam($pkName);
        } elseif (count($params = $di->dispatcher->getParams()) === 1 && isset($params[0])) {
            $pkValue = $params[0];
        }

        if ($pkValue === null) {
            return $model->assign($data, $whiteList)->create();
        } elseif (is_scalar($pkValue)) {
            return static::firstOrFail($pkValue)->assign($data, $whiteList)->update();
        } else {
            throw new InvalidValueException('primary key value is not scalar');
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
                throw new PreconditionException(['`:model` model cannot be updated because primary key value is not provided', 'model' => get_class($this)]);
            }
            return [$primaryKey => $this->$primaryKey];
        } elseif (is_array($primaryKey)) {
            $keyValue = [];
            foreach ($primaryKey as $key) {
                if (!isset($this->$key)) {
                    throw new PreconditionException(['`:model` model cannot be updated because some primary key value is not provided', 'model' => get_class($this)]);
                }
                $keyValue[$key] = $this->$key;
            }
            return $keyValue;
        } else {
            throw new NotSupportedException(['`:model` model does not has primary key', 'model' => get_called_class()]);
        }
    }

    /**
     * Deletes a model instance. Returning true on success or false otherwise.
     *
     * @return static
     */
    public function delete()
    {
        if ($this->_fireEventCancel('beforeDelete') === false) {
            return $this;
        }

        static::query(null, $this)->where($this->_getPrimaryKeyValuePairs())->delete();

        $this->_fireEvent('afterDelete');

        return $this;
    }

    /**
     * @param int|string $id
     *
     * @return static|null
     */
    public static function deleteOrFail($id = null)
    {
        $model = new static;
        $di = $model->_di;

        $pkName = $model->getPrimaryKey();

        if ($id === null) {
            if ($di->request->has($pkName)) {
                $id = $di->request->get($pkName);
            } elseif ($di->dispatcher->hasParam($pkName)) {
                $id = $di->dispatcher->getParam($pkName);
            } elseif (count($params = $di->dispatcher->getParams()) === 1 && isset($params[0])) {
                $id = $params[0];
            } else {
                throw new PreconditionException('missing primary key value');
            }
        }

        if (!is_scalar($id)) {
            throw new InvalidValueException('primary key value is not scalar');
        }

        return ($instance = static::first([$pkName => $id])) ? $instance->delete() : null;
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
            throw new PreconditionException(['getSnapshotData failed: `:model` instance is snapshot disabled', 'model' => get_class($this)]);
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
            throw new PreconditionException(['getChangedFields failed: `:model` instance is snapshot disabled', 'model' => get_class($this)]);
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
            throw new PreconditionException(['getChangedFields failed: `:model` instance is snapshot disabled', 'model' => get_class($this)]);
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
            throw new NotFoundException(['`:model` model refresh failed: `:key` record is not exists now! ', 'model' => get_called_class(), json_encode($this->_getPrimaryKeyValuePairs())]);
        }

        $data = (array)$r[0];
        foreach ($this->getJsonFields() as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                if ($data[$field] === '') {
                    $data[$field] = [];
                } elseif (($json = json_decode($data[$field], true)) === null) {
                    throw new InvalidJsonException(['`:field` field value of `:model` is not a valid json string',
                        'field' => $field,
                        'model' => get_class($this)]);
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
        foreach ((new \ReflectionClass(get_called_class()))->getConstants() as $cName => $cValue) {
            if (strpos($cName, $name) === 0) {
                $constants[$cValue] = strtolower(substr($cName, strlen($name)));
            }
        }

        if (!$constants) {
            throw new MisuseException(['starts with `:constants` constants is not exists in `:model` model', 'constants' => $name, 'model' => get_called_class()]);
        }

        return $constants;
    }

    /**
     * @param string     $field
     * @param int|double $step
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
     * @param string     $field
     * @param int|double $step
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
                'class' => get_called_class(),
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
                throw new NotSupportedException(['`:class` model does not define `:method` relation', 'class' => get_called_class(), 'method' => $relation]);
            }
        }
        throw new BadMethodCallException(['`:class` does not contain `:method` method', 'class' => get_called_class(), 'method' => $name]);
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
                foreach ((new \ReflectionClass(get_called_class()))->getConstants() as $cName => $cValue) {
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