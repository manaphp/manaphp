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
     * @param bool     $appended
     *
     * @return void
     */
    public function attachEvent($event, $handler, $appended = true);

    /**
     * @param string   $event
     * @param callable $handler
     *
     * @return void
     */
    public function detachEvent($event, $handler);

    /**
     * Fires an event in the events manager causing that the active listeners will be notified about it
     *
     * @param string $event
     * @param mixed  $source
     * @param mixed  $data
     *
     * @return void
     */
    public function fireEvent($event, $source, $data = []);

    /**
     * @param string   $group
     * @param callable $handler
     *
     * @return static
     */
    public function peekEvent($group, $handler);

    /**
     * @param string $listener
     * @param string $group
     *
     * @return static
     */
    public function addListener($listener, $group = null);
}