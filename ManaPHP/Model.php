<?php
namespace ManaPHP;

use ManaPHP\Exception\BadMethodCallException;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\InvalidJsonException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Exception\PreconditionException;
use ManaPHP\Exception\RuntimeException;
use ManaPHP\Exception\UnknownPropertyException;
use ManaPHP\Model\Expression\Increment;
use ManaPHP\Model\NotFoundException;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Model
 *
 * @package ManaPHP
 *
 * @property \ManaPHP\Model\ValidatorInterface $modelsValidator
 * @property \ManaPHP\Http\RequestInterface    $request
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
abstract class Model extends Component implements ModelInterface, \Serializable
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
    protected $_last_refresh;

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
                    } else {
                        if (($json = json_decode($data[$field], true)) === null) {
                            throw new InvalidJsonException(['`:field` field value of `:model` is not a valid json string: :error',
                                'field' => $field,
                                'model' => get_class($this),
                                'error' => json_last_error_msg()]);
                        } else {
                            $data[$field] = $json;
                        }
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
        return in_array($field, $this->getIntFields(), true) ? null : 'Y-m-d H:i:s';
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
        $criteria = static::criteria($fields ?: null)->where($filters);

        if ($options !== null) {
            if (isset($options['distinct'])) {
                $criteria->distinct($options['distinct']);
            }

            if (isset($options['order'])) {
                $criteria->orderBy($options['order']);
            }

            if (isset($options['limit'])) {
                $criteria->limit($options['limit'], isset($options['offset']) ? $options['offset'] : null);
            } elseif (isset($options['size'])) {
                $criteria->page($options['size'], isset($options['page']) ? $options['page'] : null);
            }

            if (isset($options['index'])) {
                $criteria->indexBy($options['index']);
            }

            if (isset($options['cache'])) {
                $criteria->cache($options['cache']);
            }

            if (isset($options['with'])) {
                $criteria->with($options['with']);
            }
        }

        return $criteria->fetchAll();
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
        $criteria = static::criteria($fields)->where($filters);

        if ($options !== null) {
            if (isset($options['distinct'])) {
                $criteria->distinct($options['distinct']);
            }

            if (isset($options['order'])) {
                $criteria->orderBy($options['order']);
            }

            if (isset($options['index'])) {
                $criteria->indexBy($options['index']);
            }

            if (isset($options['cache'])) {
                $criteria->cache($options['cache']);
            }

            if (isset($options['with'])) {
                $criteria->with($options['with']);
            }
        }

        return $criteria->paginate(isset($options['size']) ? $options['size'] : null, isset($options['page']) ? $options['page'] : null);
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
        foreach ($filters as $k => $v) {
            if (is_string($v)) {
                $v = trim($v);
                if ($v === '' || $v === '0') {
                    unset($filters[$k]);
                    continue;
                }
                $filters[$k] = $v;
            } elseif ($v === 0) {
                unset($filters[$k]);
            }
        }

        return static::paginate($filters, $options, $fields);
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

        $criteria = static::criteria(null, $model)->where($filters);

        $list = [];
        if ($field === null) {
            $field = $model->getDisplayField();
            if ($field === null) {
                throw new PreconditionException(['invoke :model:findList method must provide displayField', 'model' => get_called_class()]);
            }
            $keyField = $model->getPrimaryKey();
            $valueField = $field;

            $criteria = $criteria->select([$keyField, $valueField])->indexBy([$keyField => $valueField]);
            if (in_array('display_order', $model->getFields(), true)) {
                return $criteria->orderBy(['display_order' => SORT_DESC, $keyField => SORT_ASC])->execute();
            } else {
                return $criteria->orderBy($keyField)->execute();
            }
        } elseif (is_string($field)) {
            return $criteria->values($field);
        } else {
            $keyField = key($field);
            $valueField = current($field);
            foreach ($criteria->select([$keyField, $valueField])->fetchAll() as $v) {
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
     * Allows to query the first record that match the specified conditions
     *
     * @param int|string|array $filters
     * @param array            $fields
     * @param array|int|float  $options
     *
     * @return static|null
     */
    public static function first($filters = null, $fields = null, $options = null)
    {
        $model = new static;
        $di = $model->_di;

        $pkName = $model->getPrimaryKey();
        $pkValue = null;

        if ($filters === null) {
            if ($di->request->has($pkName)) {
                $pkValue = $di->request->get($pkName);
            } elseif ($di->dispatcher->hasParam($pkName)) {
                $pkValue = $di->dispatcher->getParam($pkName);
            } else {
                throw new InvalidArgumentException('missing filters condition for Model::first method');
            }

            if (!is_scalar($pkValue)) {
                throw new InvalidValueException('Model::first primary key value is not scalar');
            }
            $filters = [$pkName => $pkValue];
        } elseif (is_scalar($filters)) {
            $pkValue = $filters;
            $filters = [$pkName => $pkValue];
        } elseif (count($filters) === 1 && isset($filters[$pkName])) {
            $pkValue = $filters[$pkName];
        }

        $interval = null;
        if (is_scalar($options)) {
            $interval = (float)$options;
            $options = [];
        }

        if ($pkValue === null || $interval === null) {
            return static::criteria($fields ?: null, $model)->where($filters)->with(isset($options['with']) ? $options['with'] : [])->fetchOne();
        }

        static $cached = [];

        $current = microtime(true);
        $className = get_class($model);
        if (isset($cached[$className][$pkValue])) {
            $cache = $cached[$className][$pkValue];
            if ($current - $cache[0] <= $interval) {
                return $cache[1];
            } else {
                unset($cached[$className][$pkValue]);
            }
        }
        /**
         * @var \ManaPHP\Model $r
         */
        $r = static::criteria($fields, $model)->where($pkName, $pkValue)->fetchOne();
        if ($r) {
            $r->_snapshot = false;
        }

        $cached[$className][$pkValue] = [$current, $r];
        /** @noinspection PhpUndefinedVariableInspection */
        if (count($cached[$className]) > $model->getCacheCapacity()) {
            unset($cached[$className][key($cached[$className])]);
        }
        return $r;
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
                'No record for `:model` model with `:criteria` criteria',
                'model' => static::class,
                'criteria' => json_encode($filters, JSON_UNESCAPED_SLASHES, JSON_UNESCAPED_UNICODE)
            ]);
            $exception->model = static::class;
            $exception->filters = $filters;

            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            /** @noinspection PhpUnhandledExceptionInspection */
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

        if ($autoIncField = $model->getAutoIncrementField()) {
            $order = [$autoIncField => SORT_DESC];
        } elseif (in_array('created_time', $model->getFields(), true)) {
            $order = ['created_time' => SORT_DESC];
        } else {
            throw new BadMethodCallException('infer `:class` order condition for last failed:', ['class' => get_called_class()]);
        }

        return static::criteria($fields, $model)->where($filters)->orderBy($order)->with(isset($options['with']) ? $options['with'] : [])->fetchOne();
    }

    /**
     * @param int|string|array $filters
     * @param string           $field
     * @param int|float|array  $interval
     *
     * @return int|double|string|null
     */
    public static function value($filters, $field, $interval = null)
    {
        $model = new static;
        $pkName = $model->getPrimaryKey();

        $pkValue = null;
        if (is_scalar($filters)) {
            $pkValue = $filters;
        } elseif (is_array($filters)) {
            if (count($filters) === 1 && isset($filters[$pkName])) {
                $pkValue = $filters[$pkName];
            }
        }

        if ($interval === null || $pkValue === null) {
            $rs = static::criteria([$field], $model)->where($filters)->limit(1)->execute();
            return $rs ? $rs[0][$field] : null;
        }

        static $cached = [];

        $current = microtime(true);
        $className = get_called_class();

        if (isset($cached[$className][$field][$pkValue])) {
            $cache = $cached[$className][$field][$pkValue];
            if ($current - $cache[0] <= $interval) {
                return $cache[1];
            }
            unset($cached[$className][$field][$pkValue]);
        }

        $rs = static::criteria([$field], $model)->where($pkName, $pkValue)->limit(1)->execute();
        $value = $rs ? $rs[0][$field] : null;

        $cached[$className][$field][$pkValue] = [$current, $value];
        if (count($cached[$className][$field]) > $model->getCacheCapacity()) {
            unset($cached[$className][$field][key($cached[$className][$field])]);
        }

        return $value;
    }

    /**
     * @param int|string|array $filters
     * @param string           $field
     * @param int|float|array  $interval
     *
     * @return int|double|string
     * @throws \ManaPHP\Model\NotFoundException
     */
    public static function valueOrFail($filters, $field, $interval = null)
    {
        $value = static::value($filters, $field, $interval);
        if ($value === null) {
            throw new NotFoundException(['valueOrFail: `:model` model with `:criteria` criteria record is not exists',
                'model' => get_called_class(),
                'criteria' => json_encode($filters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        } else {
            return $value;
        }
    }

    /**
     * @param string $field
     * @param array  $filters
     * @param array  $options
     *
     * @return array
     */
    public static function values($field, $filters = null, $options = null)
    {
        $criteria = static::criteria()->where($filters);
        if (is_array($options)) {
            if (isset($options['limit'])) {
                $criteria->limit($options['limit'], isset($options['offset']) ? $options['offset'] : 0);
            } elseif (isset($options['size'])) {
                $criteria->page($options['size'], isset($options['page']) ? $options['page'] : null);
            }

            if (isset($options['distinct'])) {
                $criteria->distinct();
            }

            if (isset($options['order'])) {
                $criteria->orderBy($options['order']);
            }
        }
        return $criteria->values($field);
    }

    /**
     * @param string $field
     * @param array  $filters
     * @param array  $options
     *
     * @return array
     */
    public static function distinct($field, $filters = null, $options = null)
    {
        $criteria = static::criteria()->where($filters)->distinct();
        if (is_array($options)) {
            if (isset($options['limit'])) {
                $criteria->limit($options['limit'], isset($options['offset']) ? $options['offset'] : 0);
            } elseif (isset($options['size'])) {
                $criteria->page($options['size'], isset($options['page']) ? $options['page'] : null);
            }

            if (isset($options['order'])) {
                $criteria->orderBy($options['order']);
            }
        }
        return $criteria->values($field);
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
            return static::criteria(null, $model)->where($model->getPrimaryKey(), $filters)->exists();
        } else {
            return static::criteria()->where($filters)->exists();
        }
    }

    /**
     * Generate a SQL SELECT statement for an aggregate
     *
     * @param string $function
     * @param string $alias
     * @param string $field
     * @param array  $filters
     *
     * @return mixed
     */
    protected static function _groupResult($function, $alias, $field, $filters)
    {
        return static::criteria()->where($filters)->aggregate([$alias => "$function($field)"])[0][$alias];
    }

    /**
     * @param array $filters
     * @param array $aggregation
     * @param string|array
     *
     * @return array
     */
    public static function group($filters, $aggregation, $group = null)
    {
        return static::criteria()->where($filters)->groupBy($group)->aggregate($aggregation);
    }

    /**
     * Allows to count how many records match the specified conditions
     *
     * @param array  $filters
     * @param string $field
     *
     * @return int
     */
    public static function count($filters = null, $field = null)
    {
        $result = static::_groupResult('COUNT', 'row_count', $field ?: '*', $filters);
        if (is_string($result)) {
            $result = (int)$result;
        }

        return $result;
    }

    /**
     * Allows to calculate a summary on a field that match the specified conditions
     *
     * @param string $field
     * @param array  $filters
     *
     * @return int|float
     */
    public static function sum($field, $filters = null)
    {
        return static::_groupResult('SUM', 'summary', $field, $filters);
    }

    /**
     * Allows to get the max value of a column that match the specified conditions
     *
     * @param string $field
     * @param array  $filters
     *
     * @return int|float
     */
    public static function max($field, $filters = null)
    {
        return static::_groupResult('MAX', 'maximum', $field, $filters);
    }

    /**
     * Allows to get the min value of a column that match the specified conditions
     *
     *
     * @param string $field
     * @param array  $filters
     *
     * @return int|float
     */
    public static function min($field, $filters = null)
    {
        return static::_groupResult('MIN', 'minimum', $field, $filters);
    }

    /**
     * Allows to calculate the average value on a column matching the specified conditions
     *
     * @param string $field
     * @param array  $filters
     *
     * @return double
     */
    public static function avg($field, $filters = null)
    {
        return (double)static::_groupResult('AVG', 'average', $field, $filters);
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
        $ts = time();

        $data = [];
        if ($opMode === self::OP_CREATE) {
            $data['updated_time'] = $data['created_time'] = ($format = $this->getDateFormat('created_time')) ? date($format, $ts) : $ts;
            $data['updated_at'] = $data['created_at'] = ($format = $this->getDateFormat('created_at')) ? date($format, $ts) : $ts;
            $data['creator_id'] = $data['updator_id'] = $this->_di->identity->getId();
            $data['creator_name'] = $data['updator_name'] = $this->_di->identity->getName();
        } elseif ($opMode === self::OP_UPDATE) {
            $data['updated_time'] = ($format = $this->getDateFormat('updated_time')) ? date($format, $ts) : $ts;
            $data['updated_at'] = ($format = $this->getDateFormat('updated_at')) ? date($format, $ts) : $ts;
            $data['updator_name'] = $this->_di->identity->getName();
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
        } else {
            $params = $di->dispatcher->getParams();
            if (count($params) === 1) {
                $pkValue = current($params);
            } else {
                throw new PreconditionException('missing primary key value');
            }
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
        $primaryKey = $this->getPrimaryKey();

        if (!isset($this->{$primaryKey})) {
            return false;
        }

        return static::criteria(null, $this)->where($primaryKey, $this->{$primaryKey})->forceUseMaster()->exists();
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
        } else {
            $params = $di->dispatcher->getParams();
            if (count($params) === 1) {
                $pkValue = current($params);
            }
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
    public function getPrimaryKeyValuePairs()
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
            return [];
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

        static::criteria(null, $this)->where($this->getPrimaryKeyValuePairs())->delete();

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
            } else {
                $params = $di->dispatcher->getParams();
                if (count($params) === 1) {
                    $id = current($params);
                } else {
                    throw new PreconditionException('missing primary key value');
                }
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
        return static::criteria()->where($filters)->update($fieldValues);
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
        return static::criteria()->where($instance->getPrimaryKey(), $primaryKey)->update($fieldValues);
    }

    /**
     * @param array $filters
     *
     * @return int
     */
    public static function deleteAll($filters)
    {
        return static::criteria()->where($filters)->delete();
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
            } else {
                if ($this->$field !== null) {
                    $changed[] = $field;
                }
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

        if (is_string($fields)) {
            $fields = [$fields];
        }

        /** @noinspection ForeachSourceInspection */
        foreach ($fields as $field) {
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
            $current = microtime(true);

            if ($this->_last_refresh > 0) {
                if ($current - $this->_last_refresh < $interval) {
                    return $this;
                }
                $this->_last_refresh = $current;
            } else {
                $this->_last_refresh = $current;
                return $this;
            }
        }

        $primaryKey = $this->getPrimaryKey();

        $r = static::criteria($fields, $this)->where($primaryKey, $this->{$primaryKey})->execute();
        if (!$r) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new NotFoundException(['`:model` model refresh failed: `:key` record is not exists now! ', 'model' => get_called_class(), 'key' => $this->$primaryKey]);
        }

        foreach ((array)$r[0] as $field => $value) {
            $this->$field = $value;
        }

        if ($this->_snapshot !== false) {
            $this->_snapshot = array_merge($this->_snapshot, $r[0]);
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
        $rc = new \ReflectionClass(get_called_class());

        foreach ($rc->getConstants() as $cName => $cValue) {
            if (strpos($cName, $name) === 0) {
                $constants[$cValue] = strtolower(substr($cName, strlen($name)));
            }
        }

        if (!$constants) {
            throw new RuntimeException(['starts with `:constants` constants is not exists in `:model` model', 'constants' => $name, 'model' => get_called_class()]);
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
        } elseif ($relation = $this->_di->relationsManager->get($this, $name)) {
            return $relation->criteria($this)->fetch();
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
     * @return \ManaPHP\Model\CriteriaInterface
     * @throws \ManaPHP\Exception\BadMethodCallException
     * @throws \ManaPHP\Exception\NotSupportedException
     */
    public function __call($name, $arguments)
    {
        if (strpos($name, 'get') === 0) {
            if ($relation = $this->_di->relationsManager->get($this, lcfirst(substr($name, 3)))) {
                return $relation->criteria($this);
            }

            throw new NotSupportedException(['`:class` model does not define `:method` relation', 'class' => get_called_class(), 'method' => $name]);
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
            if (in_array($field, ['_traced', '_di', '_snapshot', '_last_refresh'], true)) {
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

    /**
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}