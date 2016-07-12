<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2016/1/18
 */

namespace ManaPHP;

interface ComponentInterface
{
    /**
     * Sets the dependency injector
     *
     * @param \ManaPHP\DiInterface $dependencyInjector
     *
     * @return static
     */
    public function setDependencyInjector($dependencyInjector);

    /**
     * Returns the internal dependency injector
     *
     * @return \ManaPHP\DiInterface
     */
    public function getDependencyInjector();

    /**
     * Attach a listener to the events manager
     *
     * @param string                                    $event
     * @param callable|\ManaPHP\Event\ListenerInterface $handler
     *
     * @return static
     * @throws \ManaPHP\Event\Exception
     */
    public function attachEvent($event, $handler);

    /**
     * Fires an event in the events manager causing that the active listeners will be notified about it
     *
     * @param string $event
     * @param mixed  $data
     *
     * @return bool
     */
    public function fireEvent($event, $data = null);

    /**
     * @param \Closure $peek
     *
     * @return void
     */
    public static function peekEvents($peek);

    /**
     * @return array
     */
    public function dump();

    /**
     * @param string $property
     *
     * @return bool
     */
    public function hasProperty($property);

    /**
     * @param string $property
     * @param mixed  $value
     *
     * @return mixed
     */
    public function setProperty($property, $value);

    /**
     * @param string $property
     *
     * @return mixed
     */
    public function getProperty($property);

    /**
     * @return array
     */
    public function getProperties();
}