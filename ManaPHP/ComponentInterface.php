<?php
namespace ManaPHP;

/**
 * Interface ManaPHP\ComponentInterface
 *
 * @package component
 */
interface ComponentInterface
{
    /**
     * Sets the dependency injector
     *
     * @param \ManaPHP\DiInterface $di
     *
     * @return void
     */
    public function setDi($di);

    /**
     * Returns the internal dependency injector
     *
     * @return \ManaPHP\Di
     */
    public function getDi();

    /**
     * Attach a listener to the events manager
     *
     * @param string   $event
     * @param callable $handler
     * @param bool     $appended
     *
     * @return static
     */
    public function attachEvent($event, $handler = null, $appended = true);

    /**
     * @param string   $event
     * @param callable $handler
     *
     * @return static
     */
    public function detachEvent($event, $handler = null);

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
     *
     * @return void
     */
    public function fireEvent($event, $data = []);

    /**
     * @param string   $event
     * @param callable $handler
     *
     * @return static
     */
    public function on($event, $handler);

    /**
     * @param array    $event
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