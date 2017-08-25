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
    }

    /**
     * Returns table name mapped in the model
     *
     * @param mixed $context
     *
     * @return string|false
     * @throws \ManaPHP\Model\Exception
     */
    public static function getSource($context = null)
    {
        $modelName = get_called_class();
        return Text::underscore(Text::contains($modelName, '\\') ? substr($modelName, strrpos($modelName, '\\') + 1) : $modelName);
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
     * @param  array       $filters
     * @param array        $options
     * @param string|array $fields
     *
     * @return  static[]
     * @throws \ManaPHP\Db\Query\Exception
     * @throws \ManaPHP\Model\Exception
     */
    public static function find($filters = [], $options = [], $fields = null)
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
                $criteria->limit($options['limit'], isset($options['offset']) ? $options['offset'] : 0);
            }
        }

        return $criteria->execute(true);
    }

    /**
     * alias of find
     *
     * @param    array     $filters
     * @param array        $options
     * @param string|array $fields
     *
     * @return  static[]
     * @throws \ManaPHP\Db\Query\Exception
     * @throws \ManaPHP\Model\Exception
     */
    final public static function findAll($filters = [], $options = null, $fields = null)
    {
        return static::find($filters, $options, $fields);
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
     * @throws \ManaPHP\Db\Query\Exception
     * @throws \ManaPHP\Model\Exception
     */
    public static function findFirst($filters = [], $fields = null)
    {
        if (is_scalar($filters)) {
            return static::findById($filters, $fields);
        }

        $criteria = static::createCriteria()
            ->select($fields ?: static::getFields())
            ->where($filters)
            ->limit(1);

        $rs = $criteria->execute(true);
        return isset($rs[0]) ? $rs[0] : false;
    }

    /**
     * @param int|string   $id
     * @param string|array $fields
     *
     * @return static|false
     * @throws \ManaPHP\Db\Query\Exception
     * @throws \ManaPHP\Model\Exception
     */
    public static function findById($id, $fields = null)
    {
        if (!is_scalar($id)) {
            throw new ModelException('`:primaryKey` primaryKey must be a scalar value.', ['primaryKey' => static::getPrimaryKey()[0]]);
        }
        $rs = static::createCriteria()->select($fields ?: static::getFields())->where(static::getPrimaryKey()[0], $id)->execute(true);
        return isset($rs[0]) ? $rs[0] : false;
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

            return static::createCriteria()->where($primaryKeys[0], $filters)->exists();
        } else {
            return static::createCriteria()->where($filters)->exists();
        }
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
        foreach (static::getFields() as $field) {
            if (!isset($data[$field])) {
                continue;
            }

            if ($whiteList !== null && !in_array($field, $whiteList, true)) {
                continue;
            }

            $this->{$field} = $data[$field];
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

        static::updateAll($fieldValues, $conditions);

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

        return static::updateAll($fieldValues, [static::getPrimaryKey()[0] => $id]);
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
            throw new ModelException('`:model` model must define a primary key in order to perform delete operation'/**m0d826d10544f3a078*/, ['model' => get_class($this)]);
        }

        if ($this->_fireEventCancel('beforeDelete') === false) {
            throw new ModelException('`:model` model cannot be deleted because it has been cancel.'/**m0d51bc276770c0f85*/, ['model' => get_class($this)]);
        }

        $conditions = [];
        foreach ($primaryKeys as $field) {
            if (!isset($this->{$field})) {
                throw new ModelException('`:model` model cannot be deleted because the primary key attribute: `:column` was not set'/**m01dec9cd3b69742a5*/,
                    ['model' => get_class($this), 'column' => $field]);
            }

            $conditions[$field] = $this->{$field};
        }

        $r = static::deleteAll($conditions);

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

        return static::deleteAll([static::getPrimaryKey()[0] => $id]);
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
}