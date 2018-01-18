<?php
namespace ManaPHP;

interface ModelInterface
{
    /**
     * Returns table name mapped in the model
     * <code>
     *  $city->getSource();
     * </code>
     *
     * @param mixed $context
     *
     * @return string|false
     */
    public static function getSource($context = null);

    /**
     * Gets internal database connection
     *
     * @param mixed $context
     *
     * @return string|false
     */
    public static function getDb($context = null);

    /**
     * @return string
     */
    public static function getPrimaryKey();

    /**
     * @return array
     */
    public static function getFields();

    /**
     * @param string $field
     *
     * @return bool
     */
    public static function hasField($field);

    /**
     * @return array|null
     */
    public static function getAccessibleFields();

    /**
     * @return string
     */
    public static function getAutoIncrementField();

    /**
     * @return string|null
     */
    public static function getDisplayField();

    /**
     * @param string|array $fields
     *
     * @return \ManaPHP\Db\Model\CriteriaInterface
     */
    public static function criteria($fields = null);

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * <code>
     *  $cities=City::find(['country_id'=>2]);
     *  $cities=City::find(['conditions'=>['country_id'=>2],'order'=>'city_id desc']);
     *  $cities=City::find([['country_id'=>2],'order'=>'city_id desc']);
     *  $cities=City::find(['conditions'=>'country_id =:country_id','bind'=>['country_id'=>2]]);
     *
     * </code>
     * @param array        $filters
     * @param array        $options
     * @param string|array $fields
     *
     * @return  static[]
     */
    public static function find($filters = [], $options = null, $fields = null);

    /**
     * @param array        $filters
     * @param array        $options
     * @param string|array $fields
     *
     * @return  \ManaPHP\PaginatorInterface
     */
    public static function paginate($filters = [], $options = null, $fields = null);

    /**
     * @param array        $filters
     * @param string|array $field
     *
     * @return mixed
     */
    public static function findList($filters = [], $field = null);

    /**
     * alias of first
     *
     * @param int|string|array $filters
     * @param string|array     $fields
     * @param array            $options
     *
     * @return static|false
     */
    public static function findFirst($filters = [], $fields = null, $options = null);

    /**
     * Allows to query the first record that match the specified conditions
     *
     * <code>
     *  $city=City::first(10);
     *  $city=City::first(['city_id'=>10]);
     *  $city=City::first(['conditions'=>['city_id'=>10]]);
     *  $city=City::first(['conditions'=>'city_id =:city_id','bind'=>['city_id'=>10]]);
     * </code>
     *
     * @param int|string|array $filters
     * @param string|array     $fields
     * @param array            $options
     *
     * @return static|false
     */
    public static function first($filters = [], $fields = null, $options = null);

    /**
     * @param int|string|array $filters
     * @param string|array     $fields
     * @param array            $options
     *
     * @return static
     */
    public static function firstOrFail($filters = [], $fields = null, $options = null);

    /**
     * @param int|string   $id
     * @param string|array $fields
     * @param array        $options
     *
     * @return static|false
     */
    public static function findById($id, $fields = null, $options = null);

    /**
     * @param int|string|array $filters
     * @param string           $field
     * @param mixed            $defaultValue
     *
     * @return int|double|string|null
     */
    public static function value($filters, $field, $defaultValue = null);

    /**
     * @param string $field
     * @param array  $filters
     * @param array  $options
     *
     * @return array
     */
    public static function values($field, $filters = null, $options = null);

    /**
     * @param string|array $filters
     *
     * @return bool
     */
    public static function exists($filters = null);

    /**
     * @param int|string $id
     *
     * @return bool
     */
    public static function existsById($id);

    /**
     * Allows to count how many records match the specified conditions
     *
     * <code>
     * City::count(['country_id'=>2]);
     * </code>
     *
     * @param array  $filters
     * @param string $field
     *
     * @return int
     */
    public static function count($filters = null, $field = null);

    /**
     * Allows to calculate a summary on a column that match the specified conditions
     *
     * @param string $field
     * @param array  $filters
     *
     * @return int|float
     */
    public static function sum($field, $filters = null);

    /**
     * Allows to get the max value of a column that match the specified conditions
     *
     * @param string $field
     * @param array  $filters
     *
     * @return int|float
     */
    public static function max($field, $filters = null);

    /**
     * Allows to get the min value of a column that match the specified conditions
     *
     * @param string $field
     * @param array  $filters
     *
     * @return int|float
     */
    public static function min($field, $filters = null);

    /**
     * Allows to calculate the average value on a column matching the specified conditions
     *
     * @param string $field
     * @param array  $filters
     *
     * @return double
     */
    public static function avg($field, $filters = null);

    /**
     * Assigns values to a model from an array
     * <code>
     *  $city->assign(['city_id'=>1,'city_name'=>'beijing']);
     *  $city->assign(['city_id'=>1,'city_name'=>'beijing'],['city_name']);
     * </code>
     *
     * @param array $data
     * @param array $whiteList
     *
     * @return static
     */
    public function assign($data, $whiteList = null);

    /**
     * Inserts or updates a model instance. Returning true on success or false otherwise.
     *
     * @return void
     */
    public function save();

    /**
     * Inserts a model instance. If the instance already exists in the persistence it will throw an exception
     * Returning true on success or false otherwise.
     *
     * @return void
     */
    public function create();

    /**
     * Updates a model instance. If the instance does n't exist in the persistence it will throw an exception
     * Returning true on success or false otherwise.
     *
     * @return void
     */
    public function update();

    /**
     * @param int|string $id
     * @param array      $data
     * @param array      $whiteList
     *
     * @return int
     */
    public static function updateById($id, $data, $whiteList = null);

    /**
     * @param array $fieldValues
     * @param array $filters
     *
     * @return int
     */
    public static function updateAll($fieldValues, $filters);

    /**
     * Deletes a model instance. Returning true on success or false otherwise.
     *
     * @return int
     */
    public function delete();

    /**
     * @param int|string $id
     *
     * @return void
     */
    public static function deleteById($id);

    /**
     * @param array $filters
     *
     * @return int
     */
    public static function deleteAll($filters);

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
    public function toArray($ignoreNull = false);

    /**
     * Returns the internal snapshot data
     *
     * @return array
     */
    public function getSnapshotData();

    /**
     * Returns a list of changed values
     *
     * @return array
     */
    public function getChangedFields();

    /**
     * Check if a specific attribute has changed
     * This only works if the model is keeping data snapshots
     *
     * @param string|array $fields
     *
     * @return bool
     */
    public function hasChanged($fields);

    /**
     * @param string       $referenceModel
     * @param array|string $referenceField
     *
     * @return \ManaPHP\Model\CriteriaInterface|false
     */
    public function hasOne($referenceModel, $referenceField = null);

    /**
     * @param string $referenceModel
     * @param string $referenceField
     *
     * @return \ManaPHP\Model\CriteriaInterface|false
     */
    public function belongsTo($referenceModel, $referenceField = null);

    /**
     * @param string $referenceModel
     * @param string $referenceField
     *
     * @return \ManaPHP\Model\CriteriaInterface
     */
    public function hasMany($referenceModel, $referenceField = null);
}