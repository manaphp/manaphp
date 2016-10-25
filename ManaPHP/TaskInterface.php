<?php
namespace ManaPHP;

/**
 * Interface ManaPHP\TaskInterface
 *
 * @package task
 */
interface TaskInterface
{
    /**
     * @return void
     */
    public function run();

    /**
     * @param int $timeLimit
     *
     * @return void
     */
    public function start($timeLimit = 0);
}
