<?php

namespace ManaPHP\Event;

interface ManagerInterface
{
    /**
     * Attach a listener to the events manager
     *
     * @param string   $event
     * @param callable $handler
     * @param int      $priority
     *
     * @return void
     */
    public function attachEvent($event, $handler, $priority = 0);

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
     * @param mixed  $data
     * @param mixed  $source
     *
     * @return \ManaPHP\Event\EventArgs
     */
    public function fireEvent($event, $data = null, $source = null);

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