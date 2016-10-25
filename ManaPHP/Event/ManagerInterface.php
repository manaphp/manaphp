<?php

namespace ManaPHP\Event;

/**
 * Interface ManaPHP\Event\ManagerInterface
 *
 * @package eventsManager
 */
interface ManagerInterface
{

    /**
     * Attach a listener to the events manager
     *
     * @param string   $event
     * @param callable $handler
     *
     * @return void
     */
    public function attachEvent($event, $handler);

    /**
     * Fires an event in the events manager causing that the active listeners will be notified about it
     *
     * @param string                         $event
     * @param \ManaPHP\Component|\ManaPHP\Di $source
     * @param array                          $data
     *
     * @return bool|null
     */
    public function fireEvent($event, $source, $data = []);

    /**
     * @param callable $handler
     *
     * @return void
     */
    public function peekEvents($handler);
}