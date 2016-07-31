<?php
namespace ManaPHP;

interface StoreInterface
{
    /**
     * Checks whether a specified key exists in the store.
     *
     * @param string $key
     *
     * @return bool
     */
    public function exists($key);

    /**
     * Retrieves a value from store with a specified key.
     *
     * @param string $key
     *
     * @return mixed|false
     */
    public function get($key);

    /**
     * Retrieves multiple values from store with corresponding keys.
     *
     * @param array $keys
     *
     * @return array
     */
    public function mGet($keys);

    /**
     * Stores a value identified by a key into store.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function set($key, $value);

    /**
     * Stores multiple values corresponding with keys into store.
     *
     * @param array $keyValues
     *
     * @return void
     */
    public function mSet($keyValues);

    /**
     * Deletes a value with the specified id from store
     *
     * @param string $key
     *
     * @void
     */
    public function delete($key);
}