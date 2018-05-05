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
    public function getMaxDelay();

    /**
     * @return int
     */
    public function getInterval();

    /**
     * @return void
     */
    public function heartbeat();

    /**
     * @return void
     */
    public function run();

    /**
     * @return string
     */
    public function getId();

    /**
     * @param string $id
     *
     * @return static
     */
    public function setId($id);
}
