<?php

namespace ManaPHP;

interface ComponentInterface
{
    /**
     * Attach a listener to the events manager
     *
     * @param string   $event
     * @param callable $handler
     * @param bool     $appended
     *
     * @return static
     */
    public function attachEvent($event, $handler, $appended = true);

    /**
     * @param string   $event
     * @param callable $handler
     *
     * @return static
     */
    public function detachEvent($event, $handler);

    /**
     * @param string   $group
     * @param callable $handler
     *
     * @return static
     */
    public function peekEvent($group, $handler);

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
     * @param string   $event
     * @param callable $handler
     *
     * @return static
     */
    public function on($event, $handler);

    /**
     * @param string   $event
     * @param callable $handler
     *
     * @return static
     */
    public function off($event = null, $handler = null);

    /**
     * @param string $event
     * @param array  $data
     *
     * @return void
     */
    public function emit($event, $data = []);
}