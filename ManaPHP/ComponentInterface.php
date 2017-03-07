<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2016/1/18
 */

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
     * @param \ManaPHP\DiInterface $dependencyInjector
     *
     * @return static
     */
    public function setDependencyInjector($dependencyInjector);

    /**
     * Returns the internal dependency injector
     *
     * @return \ManaPHP\Di
     */
    public function getDependencyInjector();

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
}