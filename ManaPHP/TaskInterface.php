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
     * @return int
     */
    public function getErrorDelay();

    /**
     * @return int
     */
    public function getInterval();
    
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
