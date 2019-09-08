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
     * @return \ManaPHP\DiInterface
     */
    public function getDi();

    /**
     * @param \ManaPHP\DiInterface $di
     *
     * @return void
     */
    public function setDi($di);

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
     * @param callable $handler
     *
     * @return static
     */
    public function peekEvent($handler);

    /**
     * @param string $listener
     * @param string $type
     *
     * @return static
     */
    public function addListener($listener, $type = null);
}