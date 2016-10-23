<?php
namespace ManaPHP\Task\Metadata;

/**
 * Interface ManaPHP\Task\Metadata\AdapterInterface
 *
 * @package ManaPHP\Task\Metadata
 */
interface AdapterInterface
{
    /**
     * @param string $key
     *
     * @return mixed|false
     */
    public function get($key);

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function set($key, $value);

    /**
     * @param string $key
     *
     * @return void
     */
    public function delete($key);

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key);
}