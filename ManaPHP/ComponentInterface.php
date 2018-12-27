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
     * @param bool     $appended
     *
     * @return static
     */
    public function attachEvent($event, $handler, $appended = true);

    /**
     * Fires an event in the events manager causing that the active listeners will be notified about it
     *
     * @param string $event
     * @param array  $data
     *
     * @return mixed|null
     */
    public function fireEvent($event, $data = []);

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