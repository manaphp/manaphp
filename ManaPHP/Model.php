<?php
namespace ManaPHP;

use ManaPHP\Model\Exception as ModelException;
use ManaPHP\Model\NotFoundException;
use ManaPHP\Utility\Text;

abstract class Model extends Component implements ModelInterface, \JsonSerializable, \Serializable
{
    /**
     * @var array
     */
    protected $_snapshot = [];

    /**
     * @var array
     */
    protected $_with = [];

    /**
     * \ManaPHP\Model constructor
     *
     * @param array $data
     */
    final public function __construct($data = [])
    {
        $this->_dependencyInjector = Di::getDefault();

        if (count($data) !== 0) {
            $this->_snapshot = $data;
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
     * @return string|false
     */
    public static function getSource($context = null)
    {
        $modelName = get_called_class();
        return Text::underscore(($pos = strrpos($modelName, '\\')) === false ? $modelName : substr($modelName, $pos + 1));
    }

    /**
     * @param string $field
     *
     * @return bool
     */
    public static function hasField($field)
    {
        return in_array($field, static::getFields(), true);
    }

    /**
     * @return array|null
     */
    public static function getAccessibleFields()
    {
        return null;
    }

    /**
     * @return string|null
     */
    public static function getDisplayField()
    {
        $fields = static::getFields();

        if (in_array('name', $fields, true)) {
            return 'name';
        }

        $primaryKey = static::getPrimaryKey();
        if ($pos = strrpos($primaryKey, '_')) {
            $tryField = substr($primaryKey, $pos + 1) . '_name';

            if (in_array($tryField, $fields, true)) {
                return $tryField;
            }
        }

        $modelName = get_called_class();
        if ($pos = strrpos($modelName, '\\')) {
            $tryField = lcfirst(substr($modelName, $pos + 1));

            if (in_array($tryField, $fields, true)) {
                return $tryField;
            }
        }

        return null;
    }

    /**
     * @return array
     */
    protected static function _getCrudTimestampFields()
    {
        static $cached = [];

        $calledClass = get_called_class();

        if (!isset($cached[$calledClass])) {
            $timestampFields = [];
            $fields = static::getFields();

            $intersect = array_intersect(['created_time', 'created_at'], $fields);
            if (isset($intersect[0])) {
                $timestampFields['create'] = $intersect[0];
            }

            $intersect = array_intersect(['updated_time', 'updated_at'], $fields);
            if (isset($intersect[0])) {
                $timestampFields['update'] = $intersect[0];
            }

            return $cached[$calledClass] = $timestampFields;
        }

        return $cached[$calledClass];
    }

    /**
     * @return int|string
     */
    protected static function _getCurrentTimestamp()
    {
        return time();
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * <code>
     *
     * //How many robots are there?
     * $robots = Robots::find();
     * echo "There are ", count($robots), "\n";
     *
     * //How many mechanical robots are there?
     * $robots = Robots::find("type='mechanical'");
     * echo "There are ", count($robots), "\n";
     *
     * //Get and print virtual robots ordered by name
     * $robots = Robots::find(array("type='virtual'", "order" => "name"));
     * foreach ($robots as $robot) {
     *       echo $robot->name, "\n";
     * }
     *
     * //Get first 100 virtual robots ordered by name
     * $robots = Robots::find(array("type='virtual'", "order" => "name", "limit" => 100));
     * foreach ($robots as $robot) {
     *       echo $robot->name, "\n";
     * }
     * </code>
     *
     * @param array        $filters
     * @param array        $options
     * @param string|array $fields
     *
     * @return  static[]
     */
    public static function find($filters = [], $options = null, $fields = null)
    {
        $criteria = static::criteria()->select($fields ?: static::getFields())->where($filters);

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
        $criteria = static::criteria()->select($fields ?: static::getFields())->where($filters);

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
        $criteria = static::criteria()->where($filters);

        $list = [];
        if ($field === null) {
            $field = static::getDisplayField();
            if ($field === null) {
                /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                throw new ModelException('invoke :model:findList method must provide displayField', ['model' => get_called_class()]);
            }
            $keyField = static::getPrimaryKey();
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
     * <code>
     * //What's the first robot in robots table?
     * $robot = Robots::first();
     * echo "The robot name is ", $robot->name;
     *
     * //What's the first mechanical robot in robots table?
     * $robot = Robots::first("type='mechanical'");
     * echo "The first mechanical robot name is ", $robot->name;
     *
     * //Get first virtual robot ordered by name
     * $robot = Robots::first(array("type='virtual'", "order" => "name"));
     * echo "The first virtual robot name is ", $robot->name;
     * </code>
     *
     * @param int|string|array $filters
     * @param string|array     $fields
     * @param array            $options
     *
     * @return static|false
     */
    public static function first($filters = null, $fields = null, $options = null)
    {
        if ($filters === null) {
            $di = Di::getDefault();

            $pkName = static::getPrimaryKey();

            if ($di->request->has($pkName)) {
                $pkValue = $di->request->get($pkName);
            } elseif ($di->dispatcher->hasParam($pkName)) {
                $pkValue = $di->dispatcher->getParam($pkName);
            } else {
                throw new ModelException('missing filters');
            }

            if (!is_scalar($pkValue)) {
                throw new ModelException('first key value is not scalar');
            }

            $filters = [$pkName => $pkValue];
        } elseif (is_scalar($filters)) {
            $filters = [static::getPrimaryKey() => $filters];
        }

        return static::criteria()->select($fields ?: static::getFields())->where($filters)->with(isset($options['with']) ? $options['with'] : [])->fetchOne();
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
            $exception = new NotFoundException('No query results for `:model` model with `:criteria` criteria',
                ['model' => static::class, 'criteria' => json_encode($filters, JSON_UNESCAPED_SLASHES, JSON_UNESCAPED_UNICODE)]);
            $exception->model = static::class;
            $exception->filters = $filters;

            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw $exception;
        }

        return $r;
    }

    /**
     * @param int|string   $id
     * @param string|array $fields
     * @param array        $options
     *
     * @return static|false
     */
    public static function findById($id, $fields = null, $options = null)
    {
        if (!is_scalar($id)) {
            /** @noinspection PhpUnhandledExceptionInspection */
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new ModelException('`:primaryKey` primaryKey must be a scalar value.', ['primaryKey' => static::getPrimaryKey()]);
        }

        return static::first([static::getPrimaryKey() => $id], $fields, $options);
    }

    /**
     * @param int|string|array $filters
     * @param string           $field
     * @param mixed            $defaultValue
     *
     * @return int|double|string|null
     */
    public static function value($filters, $field, $defaultValue = null)
    {
        if ($defaultValue === null) {
            return static::firstOrFail($filters, [$field])->{$field};
        } else {
            $r = static::first($filters, [$field])->{$field};
            return $r ? $r->{$field} : $defaultValue;
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
            $filters = [static::getPrimaryKey() => $filters];
        }

        return static::criteria()->where($filters)->exists();
    }

    /**
     * @param int|string $id
     *
     * @return bool
     * @throws \ManaPHP\Model\Exception
     */
    public static function existsById($id)
    {
        if (!is_scalar($id)) {
            throw new ModelException(' `:id` must be scalar value.', ['id' => json_encode($id)]);
        }

        return static::exists([static::getPrimaryKey() => $id]);
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
     * <code>
     *
     * //How many robots are there?
     * $number = Robots::count();
     * echo "There are ", $number, "\n";
     *
     * //How many mechanical robots are there?
     * $number = Robots::count("type='mechanical'");
     * echo "There are ", $number, " mechanical robots\n";
     *
     * </code>
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
     * <code>
     *
     * //How much are all robots?
     * $sum = Robots::sum(array('column' => 'price'));
     * echo "The total price of robots is ", $sum, "\n";
     *
     * //How much are mechanical robots?
     * $sum = Robots::sum(array("type='mechanical'", 'column' => 'price'));
     * echo "The total price of mechanical robots is  ", $sum, "\n";
     *
     * </code>
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
     * <code>
     *
     * //What is the max robot id?
     * $id = Robots::max(array('column' => 'id'));
     * echo "The max robot id is: ", $id, "\n";
     *
     * //What is the max id of mechanical robots?
     * $sum = Robots::max(array("type='mechanical'", 'column' => 'id'));
     * echo "The max robot id of mechanical robots is ", $id, "\n";
     *
     * </code>
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
     * <code>
     *
     * //What is the min robot id?
     * $id = Robots::min(array('column' => 'id'));
     * echo "The min robot id is: ", $id;
     *
     * //What is the min id of mechanical robots?
     * $sum = Robots::min(array("type='mechanical'", 'column' => 'id'));
     * echo "The min robot id of mechanical robots is ", $id;
     *
     * </code>
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
     * <code>
     *
     * //What's the average price of robots?
     * $average = Robots::average(array('column' => 'price'));
     * echo "The average price is ", $average, "\n";
     *
     * //What's the average price of mechanical robots?
     * $average = Robots::average(array("type='mechanical'", 'column' => 'price'));
     * echo "The average price of mechanical robots is ", $average, "\n";
     *
     * </code>
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
     *<code>
     *$robot->assign(array(
     *  'type' => 'mechanical',
     *  'name' => 'Boy',
     *  'year' => 1952
     *));
     *</code>
     *
     * @param array $data
     * @param array $whiteList
     *
     * @return static
     */
    public function assign($data, $whiteList = null)
    {
        if ($whiteList === null) {
            $whiteList = static::getAccessibleFields();
        }

        if ($whiteList === null) {
            /** @noinspection PhpUnhandledExceptionInspection */
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new ModelException('`:model` model do not define accessible fields.', ['model' => get_called_class()]);
        }

        $fields = static::getFields();

        if ($whiteList !== []) {
            $blackList = array_diff(array_intersect($fields, array_keys($data)), $whiteList);
            if ($blackList !== []) {
                /** @noinspection PhpUnhandledExceptionInspection */
                /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                throw new ModelException('`:blacklist` fields is not in accessible fields: `:whitelist`',
                    ['blacklist' => implode(',', $blackList), 'whitelist' => implode(',', $whiteList)]);
            }
        }

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $this->{$field} = $data[$field];
            }
        }

        if (count($data) === 1) {
            $field = key($data);
            method_exists($this, 'get' . ucfirst($field));
            $this->_with[$field] = $data[$field];
        }

        return $this;
    }

    protected function _preCreate()
    {

    }

    protected function _postCreate($connection)
    {

    }

    /**
     * @param bool $isCreate
     *
     * @return bool|string
     */
    protected function _maintainCrudTimestamp($isCreate)
    {
        $currentTimestamp = static::_getCurrentTimestamp();
        $timestampFields = static::_getCrudTimestampFields();

        if ($isCreate && isset($timestampFields['create'])) {
            $createTsField = $timestampFields['create'];

            if ($this->{$createTsField} !== null) {
                return false;
            }
            $this->$createTsField = $currentTimestamp;
        }

        if (isset($timestampFields['update'])) {
            $updateTsField = $timestampFields['update'];

            if ($this->$updateTsField === null
                || (isset($this->_snapshot[$updateTsField]) && $this->$updateTsField === $this->_snapshot[$updateTsField])) {
                $this->$updateTsField = $currentTimestamp;

                return $updateTsField;
            }
        }

        return false;
    }

    /**
     * Inserts a model instance. If the instance already exists in the persistence it will throw an exception
     * Returning true on success or false otherwise.
     *
     *<code>
     *    //Creating a new robot
     *    $robot = new Robots();
     *    $robot->type = 'mechanical';
     *    $robot->name = 'Boy';
     *    $robot->year = 1952;
     *    $robot->create();
     *
     *  //Passing an array to create
     *  $robot = new Robots();
     *  $robot->create(array(
     *      'type' => 'mechanical',
     *      'name' => 'Boy',
     *      'year' => 1952
     *  ));
     *</code>
     *
     * @return void
     */
    public function create()
    {
        $this->_maintainCrudTimestamp(true);

        $this->_preCreate();

        if ($this->_fireEventCancel('beforeSave') === false || $this->_fireEventCancel('beforeCreate') === false) {
            /** @noinspection PhpUnhandledExceptionInspection */
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new ModelException('`:model` model cannot be created because it has been cancel.'/**m092e54c70ff7ecc1a*/, ['model' => get_class($this)]);
        }

        $fieldValues = [];
        foreach (static::getFields() as $field) {
            if ($this->{$field} !== null) {
                $fieldValues[$field] = $this->{$field};
            }
        }

        if (($db = static::getDb($this)) === false) {
            /** @noinspection PhpUnhandledExceptionInspection */
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new ModelException('`:model` model db sharding for insert failed',
                ['model' => get_called_class(), 'context' => $this]);
        }

        if (($source = static::getSource($this)) === false) {
            /** @noinspection PhpUnhandledExceptionInspection */
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new ModelException('`:model` model table sharding for insert failed',
                ['model' => get_called_class(), 'context' => $this]);
        }

        $connection = $this->_dependencyInjector->getShared($db);
        $connection->insert($source, $fieldValues);

        $this->_postCreate($connection);

        $this->_snapshot = $this->toArray();

        $this->_fireEvent('afterCreate');
        $this->_fireEvent('afterSave');
    }

    /**
     * Updates a model instance. If the instance does n't exist in the persistence it will throw an exception
     * Returning true on success or false otherwise.
     *
     *<code>
     *    //Updating a robot name
     *    $robot = Robots::findFirst("id=100");
     *    $robot->name = "Biomass";
     *    $robot->update();
     *</code>
     *
     * @return void
     */
    public function update()
    {
        $conditions = [];
        $primaryKey = static::getPrimaryKey();

        if (!isset($this->{$primaryKey})) {
            /** @noinspection PhpUnhandledExceptionInspection */
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new ModelException('`:model` model cannot be updated because some primary key value is not provided'/**m0efc1ffa8444dca8d*/,
                ['model' => get_class($this)]);
        }

        $conditions[$primaryKey] = $this->{$primaryKey};

        $fieldValues = [];
        foreach (static::getFields() as $field) {
            if ($field === $primaryKey || $this->{$field} === null) {
                continue;
            }

            if (isset($this->_snapshot[$field])) {
                if (is_int($this->_snapshot[$field])) {
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
            return;
        }

        if ($updateTsField = $this->_maintainCrudTimestamp(false)) {
            $fieldValues[$updateTsField] = $this->$updateTsField;
        }

        if ($this->_fireEventCancel('beforeSave') === false || $this->_fireEventCancel('beforeUpdate') === false) {
            /** @noinspection PhpUnhandledExceptionInspection */
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new ModelException('`:model` model cannot be updated because it has been cancel.'/**m0634e5c85bbe0b638*/, ['model' => get_class($this)]);
        }

        static::criteria()->where($conditions)->update($fieldValues);

        $this->_snapshot = $this->toArray();

        $this->_fireEvent('afterUpdate');
        $this->_fireEvent('afterSave');
    }
    
    /**
     * @param array $whiteList
     * @param array $data
     *
     * @return static
     */
    public static function updateOrFail($whiteList = null, $data = null)
    {
        $di = Di::getDefault();

        if ($data === null) {
            $data = $di->request->get();
        }

        $pkName = static::getPrimaryKey();

        if (isset($data[$pkName])) {
            $pkValue = $data[$pkName];
            unset($pkName);
        } else {
            $params = $di->dispatcher->getParams();
            if (count($params) === 1) {
                $pkValue = current($params);
            } else {
                /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                throw new ModelException('missing primary key value');
            }
        }

        if (is_array($whiteList)) {
            foreach ($whiteList as $k => $v) {
                if (is_string($k)) {
                    if (isset($data[$k])) {
                        $data[$v] = $data[$k];
                        unset($data[$k]);
                    }

                    $whiteList[] = $v;
                    unset($whiteList[$k]);
                }
            }
        }

        $instance = static::firstOrFail([$pkName => $pkValue]);
        $instance->assign(array_intersect_key($data, array_flip(static::getFields())), $whiteList);
        $instance->save();

        return $instance;
    }

    /**
     * Checks if the current record already exists or not
     *
     * @return bool
     */
    protected function _exists()
    {
        $primaryKey = static::getPrimaryKey();

        if (!isset($this->{$primaryKey})) {
            return false;
        }

        return static::criteria()->where($primaryKey, $this->{$primaryKey})->forceUseMaster()->exists();
    }

    /**
     * Inserts or updates a model instance. Returning true on success or false otherwise.
     *
     *<code>
     *    //Creating a new robot
     *    $robot = new Robots();
     *    $robot->type = 'mechanical';
     *    $robot->name = 'Boy';
     *    $robot->year = 1952;
     *    $robot->save();
     *
     *    //Updating a robot name
     *    $robot = Robots::findFirst("id=100");
     *    $robot->name = "Biomass";
     *    $robot->save();
     *</code>
     *
     * @return void
     */
    public function save()
    {
        if ($this->_exists()) {
            $this->update();
        } else {
            $this->create();
        }
    }

    /**
     * Deletes a model instance. Returning true on success or false otherwise.
     *
     * <code>
     *$robot = Robots::findFirst("id=100");
     *$robot->delete();
     *
     *foreach (Robots::find("type = 'mechanical'") as $robot) {
     *   $robot->delete();
     *}
     * </code>
     *
     * @return int
     */
    public function delete()
    {
        if ($this->_fireEventCancel('beforeDelete') === false) {
            /** @noinspection PhpUnhandledExceptionInspection */
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new ModelException('`:model` model cannot be deleted because it has been cancel.'/**m0d51bc276770c0f85*/, ['model' => get_class($this)]);
        }

        $primaryKey = static::getPrimaryKey();

        $criteria = static::criteria();
        if (!isset($this->{$primaryKey})) {
            /** @noinspection PhpUnhandledExceptionInspection */
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new ModelException('`:model` model cannot be deleted because the primary key attribute: `:field` was not set'/**m01dec9cd3b69742a5*/,
                ['model' => get_class($this), 'field' => $primaryKey]);
        }

        $criteria->where($primaryKey, $this->{$primaryKey});

        $r = $criteria->delete();

        $this->_fireEvent('afterDelete');

        return $r;
    }

    /**
     * @param int|string $id
     *
     * @return int
     */
    public static function deleteById($id)
    {
        if (!is_scalar($id)) {
            /** @noinspection PhpUnhandledExceptionInspection */
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new ModelException('`:primaryKey` primaryKey must be a scalar value for delete.', ['primaryKey' => static::getPrimaryKey()]);
        }

        return static::criteria()->where(static::getPrimaryKey(), $id)->delete();
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
     *<code>
     * print_r($robot->toArray());
     *</code>
     *
     * @param bool $ignoreNull
     *
     * @return array
     */
    public function toArray($ignoreNull = false)
    {
        $data = [];

        foreach (static::getFields() as $field) {
            if (!$ignoreNull || isset($this->{$field})) {
                $data[$field] = $this->{$field};
            }
        }

        if ($this->_with) {
            $data = array_merge($data, $this->_with);
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
        return $this->_snapshot;
    }

    /**
     * Returns a list of changed values
     *
     * @return array
     */
    public function getChangedFields()
    {
        $changed = [];

        foreach (static::getFields() as $field) {
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
     * @throws \ManaPHP\Model\Exception
     */
    public function unserialize($serialized)
    {
        $unserialized = unserialize($serialized);
        $this->_snapshot = $unserialized;
        $this->assign($unserialized);
    }

    /**
     * @param string $model
     *
     * @return string
     */
    protected function _inferReferenceField($model)
    {
        /**
         * @var \ManaPHP\ModelInterface $model
         */
        $modelTail = ($pos = strrpos($model, '\\')) !== false ? substr($model, $pos + 1) : $model;
        $tryField = lcfirst($modelTail) . '_id';
        $fields = $model::getFields();
        if (in_array($tryField, $fields, true)) {
            return $tryField;
        } elseif (preg_match('#([A-Z][a-z]*)$#', $modelTail, $match) === 1) {
            $tryField = $match[1] . '_id';
            /** @noinspection NotOptimalIfConditionsInspection */
            if (in_array($tryField, $fields, true)) {
                return $tryField;
            }
        }

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        throw new ModelException('infer referenceField from `:model` failed.', ['model' => $model]);
    }

    /**
     * @param string $referenceModel
     * @param string $referenceField
     *
     * @return \ManaPHP\Model\CriteriaInterface|false
     */
    public function hasOne($referenceModel, $referenceField = null)
    {
        if ($referenceField === null) {
            $referenceField = $this->_inferReferenceField($referenceModel);
        }

        /**
         * @var \ManaPHP\Model $referenceModel
         */
        return $referenceModel::criteria()->where($referenceModel::getPrimaryKey(), $this->$referenceField)->setFetchType(false);
    }

    /**
     * @param string $referenceModel
     * @param string $referenceField
     *
     * @return \ManaPHP\Model\CriteriaInterface|false
     */
    public function belongsTo($referenceModel, $referenceField = null)
    {
        if ($referenceField === null) {
            $referenceField = $this->_inferReferenceField($referenceModel);
        }

        /**
         * @var \ManaPHP\Model $referenceModel
         */
        return $referenceModel::criteria()->where($referenceModel::getPrimaryKey(), $this->$referenceField)->setFetchType(false);
    }

    /**
     * @param string $referenceModel
     * @param string $referenceField
     *
     * @return \ManaPHP\Model\CriteriaInterface
     */
    public function hasMany($referenceModel, $referenceField = null)
    {
        if ($referenceField === null) {
            $referenceField = $this->_inferReferenceField(get_called_class());
        }

        /**
         * @var \ManaPHP\Model $referenceModel
         */
        return $referenceModel::criteria()->where($referenceField, $this->{static::getPrimaryKey()})->indexBy($referenceModel::getPrimaryKey())->setFetchType(true);
    }

    public function __get($name)
    {
        if (isset($this->_with[$name])) {
            return $this->_with[$name];
        }

        $method = 'get' . ucfirst($name);
        if (method_exists($this, $method)) {
            return $this->$name = $this->$method()->fetch();
        } elseif ($this->_dependencyInjector->has($name)) {
            return $this->{$name} = $this->_dependencyInjector->getShared($name);
        } elseif ($relation = $this->_inferRelation($method)) {
            return $relation->fetch();
        } else {
            trigger_error(strtr('`:class` does not contain `:field` field: `:fields`',
                [':class' => get_called_class(), ':field' => $name, ':fields' => implode(',', static::getFields())]), E_USER_WARNING);
            return null;
        }
    }

    /**
     * @param string $name
     *
     * @return false|\ManaPHP\Model\CriteriaInterface
     */
    protected function _inferRelation($name)
    {
        if (in_array(Text::underscore(substr($name, 3)) . '_id', static::getFields(), true)) {
            $calledClassName = get_called_class();

            if (($pos = strrpos($calledClassName, '\\')) !== false) {
                $className = substr($calledClassName, 0, $pos + 1) . substr($name, 3);
            } else {
                $className = substr($name, 3);
            }

            if (class_exists($className)) {
                return $this->hasOne($className);
            }
        }

        if (preg_match('#^(.*)(ies|es|s)$#', substr($name, 3), $match)) {
            if ($match[2] === 'ies') {
                $plainClassName = $match[1] . 'y';
            } else {
                $plainClassName = $match[1];
            }

            $calledClassName = get_called_class();
            if (($pos = strrpos($calledClassName, '\\')) !== false) {
                $className = substr($calledClassName, 0, $pos + 1) . $plainClassName;
            } else {
                $className = $plainClassName;
            }

            if (class_exists($className)) {
                return $this->hasMany($className);
            }
        }

        return false;
    }

    public function __call($name, $arguments)
    {
        if (strpos($name, 'get') === 0) {
            if ($relation = $this->_inferRelation($name)) {
                return $relation;
            }

            trigger_error(strtr('`:class` model does not define `:method` relation', [':class' => get_called_class(), ':method' => $name]), E_USER_ERROR);
        }

        trigger_error(strtr('`:class` does not contain `:method` method', [':class' => get_called_class(), ':method' => $name]), E_USER_ERROR);

        return null;
    }

    public function __debugInfo()
    {
        $data = $this->toArray();
        $data['*changed_fields*'] = $this->getChangedFields();

        return $data;
    }
}