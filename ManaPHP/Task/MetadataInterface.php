<?php
namespace ManaPHP\Task;

interface MetadataInterface
{
    /**
     * @param string|\ManaPHP\TaskInterface $task
     * @param string                        $field
     *
     * @return mixed
     */
    public function get($task, $field);

    /**
     * @param string|\ManaPHP\TaskInterface $task
     *
     * @return array
     */
    public function getAll($task);

    /**
     * @param string|\ManaPHP\TaskInterface $task
     * @param string                        $field
     * @param mixed                         $value
     *
     * @return void
     */
    public function set($task, $field, $value);

    /**
     * @param string|\ManaPHP\TaskInterface $task
     * @param string                        $field
     *
     * @return void
     */
    public function delete($task, $field);

    /**
     * @param string|\ManaPHP\TaskInterface $task
     * @param string                        $field
     *
     * @return bool
     */
    public function exists($task, $field);

    /**
     * @param string|\ManaPHP\TaskInterface $task
     *
     * @return void
     */
    public function reset($task);
}