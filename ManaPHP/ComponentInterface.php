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
     * @return static
     */
    public function setDi($di);

    /**
     * Returns the internal dependency injector
     *
     * @return \ManaPHP\Di
     */
    public function getDi();

    /**
     * @param string $name
     *
     * @return array
     */
    public function getConstants($name);

    /**
     * Attach a listener to the events manager
     *
     * @param string   $event
     * @param callable $handler
     *
     * @return static
     */
    public function attachEvent($event, $handler = null);

    /**
     * Fires an event in the events manager causing that the active listeners will be notified about it
     *
     * @param string $event
     * @param array  $data
     *
     * @return bool|null
     */
    public function fireEvent($event, $data = []);

    /**
     * @return array
     */
    public function dump();

    /**
     * @return array|false
     */
    public function saveInstanceState();

    /**
     * @param array $data
     *
     * @return void
     */
    public function restoreInstanceState($data);
}