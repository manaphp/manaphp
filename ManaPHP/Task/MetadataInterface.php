<?php
namespace ManaPHP\Task;

/**
 * Interface ManaPHP\Task\MetadataInterface
 *
 * @package ManaPHP\Task
 */
interface MetadataInterface
{
    /**
     * @param string $task
     * @param string $field
     *
     * @return mixed
     */
    public function get($task, $field);

    /**
     * @param string $task
     *
     * @return array
     */
    public function getAll($task);

    /**
     * @param string $task
     * @param string $field
     * @param mixed  $value
     *
     * @return void
     */
    public function set($task, $field, $value);

    /**
     * @param string $task
     * @param string $field
     *
     * @return void
     */
    public function delete($task, $field);

    /**
     * @param string $task
     * @param string $field
     *
     * @return bool
     */
    public function exists($task, $field);

    /**
     * @param string $task
     *
     * @return void
     */
    public function reset($task);
}