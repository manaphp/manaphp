<?php
namespace ManaPHP;

use ManaPHP\Model\Exception as ModelException;
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
abstract class Model extends Component implements ModelInterface, \JsonSerializable, \Serializable
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
        $this->_dependencyInjector = Di::getDefault();

        if (count($data) !== 0) {
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
     * @return array|null
     */
    public function getSafeFields()
    {
        return null;
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
     * @param array        $filters
     * @param array        $options
     * @param string|array $fields
     *
     * @return  static[]
     */
    public static function find($filters = [], $options = null, $fields = null)
    {
        $criteria = static::criteria($fields)->where($filters);

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
     * @param array        $filters
     * @param array        $options
     * @param string|array $fields
     *
     * @return  \ManaPHP\PaginatorInterface
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
                /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                /** @noinspection PhpUnhandledExceptionInspection */
                throw new ModelException(['invoke :model:findList method must provide displayField', 'model' => get_called_class()]);
            }
            $keyField = $model->getPrimaryKey();
            $valueField = $field;
            return $criteria->select([$keyField, $valueField])->indexBy([$keyField => $valueField])->orderBy($keyField)->execute();
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

    /**
     * alias of first
     *
     * @param string|array $filters
     * @param string|array $fields
     * @param array        $options
     *
     * @return static|false
     */
    public static function findFirst($filters = null, $fields = null, $options = null)
    {
        return static::first($filters, $fields, $options);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param int|string|array $filters
     * @param string|array     $fields
     * @param array|int|float  $options
     *
     * @return static|false
     */
    public static function first($filters = null, $fields = null, $options = null)
    {
        $model = new static;
        $di = $model->_dependencyInjector;

        $pkName = $model->getPrimaryKey();
        $pkValue = null;

        if ($filters === null) {
            if ($di->request->has($pkName)) {
                $pkValue = $di->request->get($pkName);
            } elseif ($di->dispatcher->hasParam($pkName)) {
                $pkValue = $di->dispatcher->getParam($pkName);
            } else {
                /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                /** @noinspection PhpUnhandledExceptionInspection */
                throw new ModelException('missing filters condition for Model::first method');
            }

            if (!is_scalar($pkValue)) {
                /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                /** @noinspection PhpUnhandledExceptionInspection */
                throw new ModelException('Model::first primary key value is not scalar');
            }
            $filters = [$pkName => $pkValue];
        } elseif (is_scalar($filters)) {
            $pkValue = $filters;
            $filters = [$pkName => $pkValue];
        } elseif (count($filters) === 1 && isset($filters[$pkName])) {
            $pkValue = $filters[$pkName];
        }

        $interval = null;
        if ($options === null) {
            null;
        } elseif (is_float($options)) {
            $interval = $options;
            $max = 10;
        } elseif (is_array($options) && count($options) === 1 && is_int($max = key($options))) {
            $interval = $options[$max];
        }

        if ($pkValue === null || $interval === null) {
            return static::criteria($fields, $model)->where($filters)->with(isset($options['with']) ? $options['with'] : [])->fetchOne();
        }

        static $cached = [];

        $current = microtime(true);
        $className = get_class($model);
        if (isset($cached[$className][$pkValue])) {
            $cache = $cached[$className][$pkValue];
            if ($current - $cache[0] < $interval) {
                return $cache[1];
            } else {
                unset($cached[$className][$pkValue]);
            }
        }
        /**
         * @var \ManaPHP\Model $r
         */
        $r = static::criteria($fields, $model)->where($pkName, $pkValue)->fetchOne();
        $r->_snapshot = false;
        $cached[$className][$pkValue] = [$current + $interval, $r];
        /** @noinspection PhpUndefinedVariableInspection */
        if (count($cached[$className]) > $max) {
            unset($cached[$className][key($cached[$className])]);
        }
        return $r;
    }

    /**
     * @param int|string|array $filters
     * @param string|array     $fields
     * @param array            $options
     *
     * @return static
     */
    public static function firstOrFail($filters = null, $fields = null, $options = null)
    {
        if (($r = static::first($filters, $fields, $options)) === false) {
            $exception = new NotFoundException([
                'No query results for `:model` model with `:criteria` criteria',
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
     * @param int|string|array $filters
     * @param string           $field
     * @param int|float|array  $interval
     *
     * @return int|double|string|false
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
            return $rs ? $rs[0][$field] : false;
        }

        if (is_numeric($interval)) {
            $interval = (float)$interval;
            $max = 100;
        } elseif (is_array($interval) && count($interval) === 0 && is_int($max = key($interval))) {
            $interval = (float)$interval[$max];
        } else {
            throw new ModelException(['`:interval` interval is not recognized', 'interval' => json_encode($interval, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        }

        static $cached = [];

        $current = microtime(true);
        $className = get_called_class();

        if (isset($cached[$className][$field][$pkValue])) {
            $cache = $cached[$className][$field][$pkValue];
            if ($current - $cache[0] < $interval) {
                return $cache[1];
            }
            unset($cached[$className][$field][$pkValue]);
        }

        $rs = static::criteria([$field], $model)->where($pkName, $pkValue)->limit(1)->execute();
        if (!$rs) {
            return false;
        }

        $value = $rs[0][$field];

        $cached[$className][$field][$pkValue] = [$current + $interval, $value];
        if (count($cached[$className][$field]) > $max) {
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
     */
    public static function valueOrFail($filters, $field, $interval = null)
    {
        $value = static::value($filters, $field, $interval);
        if ($value === false) {
            throw new ModelException(['valueOrFail failed: `:model` record is not exists', 'model' => get_called_class()]);
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
            /** @noinspection PhpUnhandledExceptionInspection */
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new ModelException(['`:model` model do not define accessible fields.', 'model' => get_called_class()]);
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
        $this->_dependencyInjector->modelsValidator->validate($this, $fields ?: $this->getChangedFields());
    }

    protected function _preCreate()
    {

    }

    protected function _postCreate($connection)
    {

    }

    /**
     * @param int $opMode
     *
     * @return  array
     */
    public function getAutoFilledData($opMode)
    {
        $ts = time();
        $time = date('Y-m-d H:i:s');
        $intTypeFields = $this->getIntTypeFields();

        $data = [];
        if ($opMode === self::OP_CREATE) {
            $data['created_time'] = $data['created_at'] = in_array('created_time', $intTypeFields, true) || in_array('created_at', $intTypeFields, true) ? $ts : $time;
            $data['creator_id'] = $data['updator_id'] = $this->userIdentity->getId();
            $data['creator_name'] = $data['updator_name'] = $this->userIdentity->getName();
            $data['updated_time'] = $data['updated_at'] = in_array('updated_time', $intTypeFields, true) || in_array('updated_at', $intTypeFields, true) ? $ts : $time;
        } elseif ($opMode === self::OP_UPDATE) {
            $data['updated_time'] = $data['updated_at'] = in_array('updated_time', $intTypeFields, true) || in_array('updated_at', $intTypeFields, true) ? $ts : $time;
            $data['updator_id'] = $this->userIdentity->getId();
            $data['updator_name'] = $this->userIdentity->getName();
        }

        return $data;
    }

    /**
     * Inserts a model instance. If the instance already exists in the persistence it will throw an exception
     *
     * @return static
     */
    public function create()
    {
        $fields = $this->getFields();
        foreach ($this->getAutoFilledData(self::OP_CREATE) as $field => $value) {
            /** @noinspection NotOptimalIfConditionsInspection */
            if (!in_array($field, $fields, true) || $this->$field !== null) {
                continue;
            }
            $this->$field = $value;
        }

        $this->validate($fields);

        $this->_preCreate();

        if ($this->_fireEventCancel('beforeSave') === false || $this->_fireEventCancel('beforeCreate') === false) {
            /** @noinspection PhpUnhandledExceptionInspection */
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new ModelException(['`:model` model cannot be created because it has been cancel.'/**m092e54c70ff7ecc1a*/, 'model' => get_class($this)]);
        }

        $fieldValues = [];
        foreach ($fields as $field) {
            if ($this->{$field} !== null) {
                $fieldValues[$field] = $this->{$field};
            }
        }

        $db = $this->getDb($this);
        $source = $this->getSource($this);

        $connection = $this->_dependencyInjector->getShared($db);
        $connection->insert($source, $fieldValues);

        $this->_postCreate($connection);

        $this->_snapshot = $this->toArray();

        $this->_fireEvent('afterCreate');
        $this->_fireEvent('afterSave');

        return $this;
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
            $data = $model->_dependencyInjector->request->get();
        }

        unset($data[$model->getPrimaryKey()]);

        $model->assign($data, $whiteList);

        return $model;
    }

    /**
     * Updates a model instance. If the instance does n't exist in the persistence it will throw an exception
     *
     * @return static
     */
    public function update()
    {
        if ($this->_snapshot === false) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new ModelException(['update failed: `:model` instance is snapshot disabled', 'model' => get_class($this)]);
        }

        $primaryKey = $this->getPrimaryKey();

        if (!isset($this->{$primaryKey})) {
            /** @noinspection PhpUnhandledExceptionInspection */
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new ModelException([
                '`:model` model cannot be updated because some primary key value is not provided'/**m0efc1ffa8444dca8d*/,
                'model' => get_class($this)
            ]);
        }

        $fieldValues = [];

        $fields = $this->getFields();
        foreach ($fields as $field) {
            if ($field === $primaryKey || $this->{$field} === null) {
                continue;
            }

            if (isset($this->_snapshot[$field])) {
                if (is_int($this->_snapshot[$field])) {
                    /** @noinspection TypeUnsafeComparisonInspection */
                    if ($this->_snapshot[$field] == $this->{$field}) {
                        continue;
                    }
                } else {
                    if ($this->_snapshot[$field] === $this->{$field}) {
                        continue;
                    }
                }
            }

            $fieldValues[$field] = $this->{$field};
        }

        if (count($fieldValues) === 0) {
            return $this;
        }

        foreach ($this->getAutoFilledData(self::OP_UPDATE) as $field => $value) {
            if (!in_array($field, $fields, true)) {
                continue;
            }

            $this->$field = $value;
            $fieldValues[$field] = $value;
        }

        $this->validate(array_keys($fieldValues));

        if ($this->_fireEventCancel('beforeSave') === false || $this->_fireEventCancel('beforeUpdate') === false) {
            /** @noinspection PhpUnhandledExceptionInspection */
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new ModelException(['`:model` model cannot be updated because it has been cancel.'/**m0634e5c85bbe0b638*/, 'model' => get_class($this)]);
        }

        static::criteria(null, $this)->where($primaryKey, $this->$primaryKey)->update($fieldValues);

        $this->_snapshot = $this->toArray();

        $this->_fireEvent('afterUpdate');
        $this->_fireEvent('afterSave');

        return $this;
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
        $di = $model->_dependencyInjector;

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
                /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                /** @noinspection PhpUnhandledExceptionInspection */
                throw new ModelException('missing primary key value');
            }
        }

        if (!is_scalar($pkValue)) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new ModelException('primary key value is not scalar');
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
        $di = $model->_dependencyInjector;

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
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new ModelException('primary key value is not scalar');
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
            /** @noinspection PhpUnhandledExceptionInspection */
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new ModelException(['`:model` model cannot be deleted because it has been cancel.'/**m0d51bc276770c0f85*/, 'model' => get_class($this)]);
        }

        $primaryKey = $this->getPrimaryKey();

        $criteria = static::criteria(null, $this);
        if (!isset($this->{$primaryKey})) {
            /** @noinspection PhpUnhandledExceptionInspection */
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new ModelException([
                '`:model` model cannot be deleted because the primary key attribute: `:field` was not set'/**m01dec9cd3b69742a5*/,
                'model' => get_class($this),
                'field' => $primaryKey
            ]);
        }

        $criteria->where($primaryKey, $this->{$primaryKey})->delete();

        $this->_fireEvent('afterDelete');

        return $this;
    }

    /**
     * @param int|string $id
     *
     * @return static
     */
    public static function deleteOrFail($id = null)
    {
        $model = new static;
        $di = $model->_dependencyInjector;

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
                    /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                    throw new ModelException('missing primary key value');
                }
            }
        }

        if (!is_scalar($id)) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new ModelException('primary key value is not scalar');
        }

        $instance = static::firstOrFail([$pkName => $id]);
        $instance->delete();

        return $instance;
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
     * @param bool $ignoreNull
     *
     * @return array
     */
    public function toArray($ignoreNull = false)
    {
        $data = [];

        foreach (get_object_vars($this) as $field => $value) {
            if ($field[0] === '_') {
                continue;
            }

            if (!$ignoreNull || $value !== null) {
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
            throw new ModelException(['getSnapshotData failed: `:model` instance is snapshot disabled', 'model' => get_class($this)]);
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
            throw new ModelException(['getChangedFields failed: `:model` instance is snapshot disabled', 'model' => get_class($this)]);
        }

        $changed = [];

        foreach ($this->getFields() as $field) {
            if (!isset($this->_snapshot[$field]) || $this->{$field} !== $this->_snapshot[$field]) {
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
            throw new ModelException(['getChangedFields failed: `:model` instance is snapshot disabled', 'model' => get_class($this)]);
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
            throw new ModelException(['`:model` model refresh failed: record is not exists now!', 'model' => get_called_class()]);
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
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray(true);
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

        foreach ($unserialized as $field => $value) {
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
     */
    public function getConstants($name)
    {
        $name = strtoupper($name) . '_';
        $constants = [];
        $rc = new \ReflectionClass($this);

        foreach ($rc->getConstants() as $cName => $cValue) {
            if (strpos($cName, $name) === 0) {
                $constants[$cValue] = strtolower(substr($cName, strlen($name)));
            }
        }

        if (!$constants) {
            throw new ModelException(['starts with `:constants` constants is not exists in `:model` model ', 'constants' => $name, 'model' => get_class($this)]);
        }

        return $constants;
    }

    /**
     * @param string $name
     *
     * @return array
     * @throws \ManaPHP\Model\Exception
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
            throw new ModelException(['starts with `:constants` constants is not exists in `:model` model', 'constants' => $name, 'model' => get_called_class()]);
        }

        return $constants;
    }

    public function __get($name)
    {
        $method = 'get' . ucfirst($name);
        if (method_exists($this, $method)) {
            return $this->$name = $this->$method()->fetch();
        } elseif ($this->_dependencyInjector->has($name)) {
            return $this->{$name} = $this->_dependencyInjector->getShared($name);
        } elseif ($relation = $this->_dependencyInjector->relationsManager->get($this, $name)) {
            return $relation->criteria($this)->fetch();
        } else {
            trigger_error(strtr('`:class` does not contain `:field` field: `:fields`',
                [':class' => get_called_class(), ':field' => $name, ':fields' => implode(',', $this->getFields())]), E_USER_WARNING);
            return null;
        }
    }

    public function __call($name, $arguments)
    {
        if (strpos($name, 'get') === 0) {
            if ($relation = $this->_dependencyInjector->relationsManager->get($this, lcfirst(substr($name, 3)))) {
                return $relation->criteria($this);
            }

            trigger_error(strtr('`:class` model does not define `:method` relation', [':class' => get_called_class(), ':method' => $name]), E_USER_ERROR);
        }

        trigger_error(strtr('`:class` does not contain `:method` method', [':class' => get_called_class(), ':method' => $name]), E_USER_ERROR);

        return null;
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        $data = $this->toArray();

        if ($this->_snapshot) {
            $data['*changed_fields*'] = $this->getChangedFields();
        }

        foreach ($this->getFields() as $field) {
            $value = $this->$field;

            if (is_int($value)) {
                if ($value > 100000000 /**1973/3/3 17:46:40*/ && strpos($field, '_id') === false) {
                    $data['*human_time*'][$field] = date('Y-m-d H:i:s', $value);
                }
            }

            if (is_numeric($value)) {
                foreach ((new \ReflectionClass(get_called_class()))->getConstants() as $cName => $cValue) {
                    if ($cValue == $value && strpos($cName, strtoupper($field)) === 0) {
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
        return json_encode($this->toArray(true), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}