<?php
namespace ManaPHP;

/**
 * Interface ManaPHP\StoreInterface
 *
 * @package ManaPHP
 */
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
     * Stores a value identified by a key into store.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function set($key, $value);

    /**
     * Deletes a value with the specified id from store
     *
     * @param string $key
     *
     * @void
     */
    public function delete($key);
}