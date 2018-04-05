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
     * @return string
     */
    public function getSource($context = null);

    /**
     * Gets internal database connection
     *
     * @param mixed $context
     *
     * @return string
     */
    public function getDb($context = null);

    /**
     * @return string
     */
    public function getPrimaryKey();

    /**
     * @return array
     */
    public function getForeignKeys();

    /**
     * @return array
     */
    public function getFields();

    /**
     * @param string $field
     *
     * @return bool
     */
    public function hasField($field);

    /**
     * @return array|null
     */
    public function getSafeFields();

    /**
     * @return array|null
     */
    public function getIntTypeFields();

    /**
     * @return string|null
     */
    public function getAutoIncrementField();

    /**
     * @return string|null
     */
    public function getDisplayField();

    /**
     * @return array
     */
    public function rules();

    /**
     * @param string|array   $fields
     * @param \ManaPHP\Model $model
     *
     * @return \ManaPHP\Db\Model\CriteriaInterface
     */
    public static function criteria($fields = null, $model = null);

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
     * @return array
     */
    public static function lists($filters = [], $field = null);

    /**
     * alias of first
     *
     * @param int|string|array $filters
     * @param string|array     $fields
     * @param array            $options
     *
     * @return static|false
     */
    public static function findFirst($filters = null, $fields = null, $options = null);

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
    public static function first($filters = null, $fields = null, $options = null);

    /**
     * @param int|string|array $filters
     * @param string|array     $fields
     * @param array            $options
     *
     * @return static
     */
    public static function firstOrFail($filters = null, $fields = null, $options = null);

    /**
     * @param int|string|array $filters
     * @param string           $field
     * @param int|float|array  $interval
     *
     * @return int|double|string|false
     */
    public static function value($filters, $field, $interval = null);

    /**
     * @param int|string|array $filters
     * @param string           $field
     * @param int|float|array  $interval
     *
     * @return int|double|string
     */
    public static function valueOrFail($filters, $field, $interval = null);

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
    public static function exists($filters);

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
     * @param string|array $fields
     *
     * @return void
     */
    public function validate($fields = null);

    /**
     * Inserts or updates a model instance. Returning true on success or false otherwise.
     *
     * @return static
     */
    public function save();

    /**
     * @param array $data
     * @param array $whiteList
     *
     * @return static
     */
    public static function saveOrFail($data = null, $whiteList = null);

    /**
     * Inserts a model instance. If the instance already exists in the persistence it will throw an exception
     * Returning true on success or false otherwise.
     *
     * @return static
     */
    public function create();

    /**
     * @param array $data
     * @param array $whiteList
     *
     * @return static
     */
    public static function createOrFail($data = null, $whiteList = null);

    /**
     * @param array $data
     * @param array $whiteList
     *
     * @return static
     */
    public static function newOrFail($data = null, $whiteList = null);

    /**
     * Updates a model instance. If the instance does n't exist in the persistence it will throw an exception
     * Returning true on success or false otherwise.
     *
     * @return static
     */
    public function update();

    /**
     * @param array $data
     * @param array $whiteList
     *
     * @return static
     */
    public static function updateOrFail($data = null, $whiteList = null);

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
     * @return static
     */
    public function delete();

    /**
     * @param int|string $id
     *
     * @return static|null
     */
    public static function deleteOrFail($id = null);

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
     * @return static
     */
    public function disableSnapshot();

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
     * @param float $interval
     * @param array $fields
     *
     * @return static
     */
    public function refresh($interval, $fields = null);
}