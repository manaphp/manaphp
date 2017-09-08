<?php
namespace ManaPHP;

use ManaPHP\Model\Exception as ModelException;
use ManaPHP\Utility\Text;

abstract class Model extends Component implements ModelInterface, \JsonSerializable, \Serializable
{
    /**
     * @var array
     */
    protected $_snapshot = [];

    /**
     * \ManaPHP\Model constructor
     *
     * @param array $data
     */
    final public function __construct($data = [])
    {
        if (count($data) !== 0) {
            $this->_snapshot = $data;
            foreach ($data as $field => $value) {
                $this->{$field} = $value;
            }

            if (method_exists($this, 'afterFetch')) {
                $this->afterFetch();
            }
        }
        $this->_dependencyInjector = Di::getDefault();
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
        if (count($primaryKey) === 1 && ($pos = strrpos($primaryKey[0], '_'))) {
            $tryField = substr($primaryKey[0], $pos + 1) . '_name';

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
        $criteria = static::createCriteria()->select($fields ?: static::getFields());

        if (isset($filters[0])) {
            $criteria->inWhere(static::getPrimaryKey()[0], $filters);
        } else {
            $criteria->where($filters);
        }

        if ($options !== null) {
            if (isset($options['distinct'])) {
                $criteria->distinct($options['distinct']);
            }

            if (isset($options['order'])) {
                $criteria->orderBy($options['order']);
            }

            if (isset($options['limit'])) {
                $criteria->limit($options['limit'], isset($options['offset']) ? $options['offset'] : null);
            }
        }

        return $criteria->fetchAll();
    }

    /**
     * alias of find
     *
     * @param array        $filters
     * @param array        $options
     * @param string|array $fields
     *
     * @return  static[]
     */
    final public static function findAll($filters = [], $options = null, $fields = null)
    {
        return static::find($filters, $options, $fields);
    }

    /**
     * @param array  $filters
     * @param string $displayField
     *
     * @return array
     */
    public static function findList($filters = [], $displayField = null)
    {
        $displayField = $displayField ?: static::getDisplayField();
        if ($displayField === null) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new ModelException('invoke :model:findList method must provide displayField', ['model' => get_called_class()]);
        }
        $primaryKey = static::getPrimaryKey()[0];
        $criteria = static::createCriteria()->select([$primaryKey, $displayField])->where($filters);

        $list = [];
        foreach ($criteria->fetchAll() as $v) {
            $list[$v->{$primaryKey}] = $v->{$displayField};
        }

        return $list;
    }

    /**
     * alias of findFirst
     *
     * @param array        $filters
     * @param string|array $fields
     *
     * @return false|static
     */
    public static function findFirst($filters = [], $fields = null)
    {
        return static::findOne($filters, $fields);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * <code>
     *
     * //What's the first robot in robots table?
     * $robot = Robots::findFirst();
     * echo "The robot name is ", $robot->name;
     *
     * //What's the first mechanical robot in robots table?
     * $robot = Robots::findFirst("type='mechanical'");
     * echo "The first mechanical robot name is ", $robot->name;
     *
     * //Get first virtual robot ordered by name
     * $robot = Robots::findFirst(array("type='virtual'", "order" => "name"));
     * echo "The first virtual robot name is ", $robot->name;
     *
     * </code>
     *
     * @param string|array $filters
     * @param string|array $fields
     *
     * @return static|false
     */
    public static function findOne($filters = [], $fields = null)
    {
        if (is_scalar($filters)) {
            $filters = [static::getPrimaryKey()[0] => $filters];
        }

        return static::createCriteria()->select($fields ?: static::getFields())->where($filters)->fetchOne();
    }

    /**
     * @param int|string   $id
     * @param string|array $fields
     *
     * @return static|false
     */
    public static function findById($id, $fields = null)
    {
        if (!is_scalar($id)) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new ModelException('`:primaryKey` primaryKey must be a scalar value.', ['primaryKey' => static::getPrimaryKey()[0]]);
        }

        return static::createCriteria()->select($fields ?: static::getFields())->where(static::getPrimaryKey()[0], $id)->fetchOne();
    }

    /**
     * @param int|string|array $filters
     *
     * @return bool
     * @throws \ManaPHP\Model\Exception
     */
    public static function exists($filters = null)
    {
        if (is_scalar($filters)) {
            $primaryKeys = static::getPrimaryKey();

            if (count($primaryKeys) === 0) {
                throw new ModelException('parameter is scalar, but the primary key of `:model` model is none', ['model' => get_called_class()]);
            }

            if (count($primaryKeys) !== 1) {
                throw new ModelException('parameter is scalar, but the primary key of `:model` model has more than one column'/**m0a5878bf7ea49c559*/,
                    ['model' => get_called_class()]);
            }
            $filters = [$primaryKeys[0] => $filters];
        }

        return static::createCriteria()->where($filters)->exists();
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

        return static::exists([static::getPrimaryKey()[0] => $id]);
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
     * @throws \ManaPHP\Model\Exception
     */
    protected static function _groupResult($function, $alias, $field, $filters)
    {
        return static::createCriteria()->where($filters)->aggregate([$alias => "$function($field)"])[0][$alias];
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
     * @throws \ManaPHP\Model\Exception
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
     * Allows to calculate a summary on a column that match the specified conditions
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
     * @throws \ManaPHP\Model\Exception
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
     * @throws \ManaPHP\Model\Exception
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
     * @throws \ManaPHP\Model\Exception
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
     * @throws \ManaPHP\Model\Exception
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
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new ModelException('`:model` model do not define accessible fields.', ['model' => get_called_class()]);
        }

        $fields = static::getFields();

        if ($whiteList !== []) {
            $blackList = array_diff(array_intersect($fields, array_keys($data)), $whiteList);
            if ($blackList !== []) {
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

        return $this;
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
     * @throws \ManaPHP\Model\Exception
     */
    public function update()
    {
        $conditions = [];
        $primaryKey = static::getPrimaryKey();

        foreach ($primaryKey as $field) {
            if (!isset($this->{$field})) {
                throw new ModelException('`:model` model cannot be updated because some primary key value is not provided'/**m0efc1ffa8444dca8d*/,
                    ['model' => get_class($this)]);
            }

            $conditions[$field] = $this->{$field};
        }

        $fieldValues = [];
        foreach (static::getFields() as $field) {
            if (in_array($field, $primaryKey, true)) {
                continue;
            }

            if (isset($this->{$field})) {
                /** @noinspection NestedPositiveIfStatementsInspection */
                if (!isset($this->_snapshot[$field]) || $this->{$field} !== $this->_snapshot[$field]) {
                    $fieldValues[$field] = $this->{$field};
                }
            }
        }

        if (count($fieldValues) === 0) {
            return;
        }

        if ($this->_fireEventCancel('beforeSave') === false || $this->_fireEventCancel('beforeUpdate') === false) {
            throw new ModelException('`:model` model cannot be updated because it has been cancel.'/**m0634e5c85bbe0b638*/, ['model' => get_class($this)]);
        }

        static::createCriteria()->where($conditions)->update($fieldValues);

        $this->_snapshot = $this->toArray();

        $this->_fireEvent('afterUpdate');
        $this->_fireEvent('afterSave');
    }

    /**
     * @param int|string $id
     * @param array      $data
     * @param array      $whiteList
     *
     * @return int
     * @throws \ManaPHP\Model\Exception
     */
    public static function updateById($id, $data, $whiteList = null)
    {
        if (!is_scalar($id)) {
            throw new ModelException('`:primaryKey` primaryKey must be a scalar value for delete.', ['primaryKey' => static::getPrimaryKey()[0]]);
        }

        $fieldValues = [];
        foreach (static::getFields() as $field) {
            if (!isset($data[$field])) {
                continue;
            }

            if ($whiteList !== null && !in_array($field, $whiteList, true)) {
                continue;
            }

            $fieldValues[$field] = $data[$field];
        }
        return static::createCriteria()->where(static::getPrimaryKey()[0], $id)->update($fieldValues);
    }

    /**
     * Checks if the current record already exists or not
     *
     * @return bool
     */
    protected function _exists()
    {
        $primaryKeys = static::getPrimaryKey();
        if (count($primaryKeys) === 0) {
            return false;
        }

        $conditions = [];

        foreach ($primaryKeys as $field) {
            if (!isset($this->{$field})) {
                return false;
            }
            $conditions[$field] = $this->{$field};
        }

        if (is_array($this->_snapshot)) {
            $primaryKeyEqual = true;
            foreach ($primaryKeys as $field) {
                if (!isset($this->_snapshot[$field]) || $this->_snapshot[$field] !== $this->{$field}) {
                    $primaryKeyEqual = false;
                }
            }

            if ($primaryKeyEqual) {
                return true;
            }
        }

        return static::createCriteria()->where($conditions)->forceUseMaster()->exists();
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
     * @throws \ManaPHP\Model\Exception
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
     * @throws \ManaPHP\Model\Exception
     */
    public function delete()
    {
        $primaryKeys = static::getPrimaryKey();

        if (count($primaryKeys) === 0) {
            throw new ModelException('`:model` model must define a primary key in order to perform delete operation'/**m0d826d10544f3a078*/,
                ['model' => get_class($this)]);
        }

        if ($this->_fireEventCancel('beforeDelete') === false) {
            throw new ModelException('`:model` model cannot be deleted because it has been cancel.'/**m0d51bc276770c0f85*/, ['model' => get_class($this)]);
        }

        $criteria = static::createCriteria();
        foreach ($primaryKeys as $field) {
            if (!isset($this->{$field})) {
                throw new ModelException('`:model` model cannot be deleted because the primary key attribute: `:column` was not set'/**m01dec9cd3b69742a5*/,
                    ['model' => get_class($this), 'column' => $field]);
            }

            $criteria->where($field, $this->{$field});
        }

        $r = $criteria->delete();

        $this->_fireEvent('afterDelete');

        return $r;
    }

    /**
     * @param int|string $id
     *
     * @return int
     * @throws \ManaPHP\Model\Exception
     */
    public static function deleteById($id)
    {
        if (!is_scalar($id)) {
            throw new ModelException('`:primaryKey` primaryKey must be a scalar value for delete.', ['primaryKey' => static::getPrimaryKey()[0]]);
        }

        return static::createCriteria()->where(static::getPrimaryKey()[0], $id)->delete();
    }

    /**
     * @param array $fieldValues
     * @param array $filters
     *
     * @return int
     * @throws \ManaPHP\Model\Exception
     */
    public static function updateAll($fieldValues, $filters)
    {
        return static::createCriteria()->where($filters)->update($fieldValues);
    }

    /**
     * @param array $filters
     *
     * @return int
     * @throws \ManaPHP\Model\Exception
     */
    public static function deleteAll($filters)
    {
        return static::createCriteria()->where($filters)->delete();
    }

    /**
     * Returns the instance as an array representation
     *
     *<code>
     * print_r($robot->toArray());
     *</code>
     *
     * @return array
     */
    public function toArray()
    {
        $data = [];

        foreach (static::getFields() as $field) {
            $data[$field] = isset($this->{$field}) ? $this->{$field} : null;
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
        return $this->toArray();
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
        return $referenceModel::createCriteria()->where($referenceModel::getPrimaryKey()[0], $this->$referenceField)->setFetchType(false);
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
        return $referenceModel::createCriteria()->where($referenceModel::getPrimaryKey()[0], $this->$referenceField)->setFetchType(false);
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
        return $referenceModel::createCriteria()->where($referenceField, $this->{static::getPrimaryKey()[0]})->setFetchType(true);
    }

    public function __get($name)
    {
        $method = 'get' . $name;
        if (method_exists($this, $method)) {

            return $this->$name = $this->$method()->fetch();
        } else {
            return parent::__get($name);
        }
    }

    public function __debugInfo()
    {
        $data = $this->toArray();
        $data['*changed_fields*'] = $this->getChangedFields();

        return $data;
    }
}