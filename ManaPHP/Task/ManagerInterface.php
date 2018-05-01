<?php
namespace ManaPHP\Task;

interface ManagerInterface
{
    /**
     * @param string $task
     */
    public function run($task);
}