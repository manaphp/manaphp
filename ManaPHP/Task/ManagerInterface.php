<?php

namespace ManaPHP\Task;

interface ManagerInterface
{
    /**
     * @param string $task
     */
    public function run($task);

    /**
     * @param string $task
     *
     * @void
     */
    public function heartbeat($task);
}