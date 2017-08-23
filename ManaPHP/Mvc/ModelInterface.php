<?php

namespace ManaPHP\Mvc;

/**
 * Interface ManaPHP\Mvc\ModelInterface
 *
 * @package model
 */
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
     * @return \ManaPHP\DbInterface|false
     */
    public static function getDb($context = null);

    /**
     * @param mixed $context
     *
     * @return \ManaPHP\DbInterface|false
     */
    public static function getConnection($context = null);

    /**
     * @return array
     */
    public static function getPrimaryKey();

    /**
     * @return array
     */
    public static function getFields();

    /**
     * Create a criteria for a specific model
     *
     * @param string $alias
     *
     * @return \ManaPHP\Mvc\Model\QueryInterface
     */
    public static function createQuery($alias = null);

    /**
     * @param string|array $columns
     *
     * @return \ManaPHP\Mvc\Model\CriteriaInterface
     */
    public static function createCriteria($columns = null);

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
     * @param    string|array $parameters
     *
     * @return  static[]
     */
    public static function find($parameters = null);

    /**
     * alias of find
     *
     * @param    string|array $parameters
     *
     * @return  static[]
     */
    public static function findAll($parameters = null);

    /**
     * Allows to query the first record that match the specified conditions
     *
     * <code>
     *  $city=City::findFirst(10);
     *  $city=City::findFirst(['city_id'=>10]);
     *  $city=City::findFirst(['conditions'=>['city_id'=>10]]);
     *  $city=City::findFirst(['conditions'=>'city_id =:city_id','bind'=>['city_id'=>10]]);
     * </code>
     *
     * @param string|array $parameters
     *
     * @return static|false
     */
    public static function findFirst($parameters = null);

    /**
     * @param int|string   $id
     * @param string|array $fields
     *
     * @return static|false
     */
    public static function findById($id, $fields = null);

    /**
     * @param string|array $parameters
     *
     * @return bool
     */
    public static function exists($parameters = null);

    /**
     * Create a criteria for a special model
     *
     * @param string $alias
     *
     * @return \ManaPHP\Mvc\Model\QueryInterface
     * @deprecated
     */
    public static function query($alias = null);

    /**
     * Allows to count how many records match the specified conditions
     *
     * <code>
     * City::count(['country_id'=>2]);
     * </code>
     *
     * @param string|array $parameters
     * @param string       $column
     *
     * @return int|array
     */
    public static function count($parameters = null, $column = '*');

    /**
     * Allows to calculate a summary on a column that match the specified conditions
     *
     * @param string       $column
     * @param string|array $parameters
     *
     * @return mixed
     */
    public static function sum($column, $parameters = null);

    /**
     * Allows to get the max value of a column that match the specified conditions
     *
     * @param string       $column
     * @param string|array $parameters
     *
     * @return mixed
     */
    public static function max($column, $parameters = null);

    /**
     * Allows to get the min value of a column that match the specified conditions
     *
     * @param string       $column
     * @param string|array $parameters
     *
     * @return mixed
     */
    public static function min($column, $parameters = null);

    /**
     * Allows to calculate the average value on a column matching the specified conditions
     *
     * @param string       $column
     * @param string|array $parameters
     *
     * @return double
     */
    public static function avg($column, $parameters = null);

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
     * @param  array $data
     * @param  array $whiteList
     *
     * @return void
     */
    public function save($data = null, $whiteList = null);

    /**
     * Inserts a model instance. If the instance already exists in the persistence it will throw an exception
     * Returning true on success or false otherwise.
     *
     * @param  array $data
     * @param  array $whiteList
     *
     * @return void
     */
    public function create($data = null, $whiteList = null);

    /**
     * Updates a model instance. If the instance does n't exist in the persistence it will throw an exception
     * Returning true on success or false otherwise.
     *
     * @param  array $data
     * @param  array $whiteList
     *
     * @return void
     */
    public function update($data = null, $whiteList = null);

    /**
     * @param int|string $id
     * @param array      $data
     * @param array      $whiteList
     *
     * @return int
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public static function updateById($id, $data, $whiteList = null);

    /**
     * @param array        $columnValues
     * @param string|array $conditions
     * @param array        $bind
     *
     * @return int
     */
    public static function updateAll($columnValues, $conditions, $bind = []);

    /**
     * Deletes a model instance. Returning true on success or false otherwise.
     *
     * @return void
     */
    public function delete();

    /**
     * @param int|string $id
     *
     * @return void
     */
    public static function deleteById($id);

    /**
     * @param string|array $conditions
     * @param array        $bind
     *
     * @return int
     */
    public static function deleteAll($conditions, $bind = []);

    /**
     * Returns the instance as an array representation
     *
     *<code>
     * print_r($robot->toArray());
     *</code>
     *
     * @return array
     */
    public function toArray();

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
}