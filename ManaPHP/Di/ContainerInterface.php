<?php

namespace ManaPHP\Di;

interface ContainerInterface
{
    /**
     * @param string   $event
     * @param callable $handler
     *
     * @return static
     */
    public function on($event, $handler);

    /**
     * Registers an "always shared" component in the components container
     *
     * @param string $name
     * @param mixed  $definition
     *
     * @return static
     */
    public function set($name, $definition);

    /**
     * @return array
     */
    public function getDefinitions();

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getDefinition($name);

    /**
     * @return array
     */
    public function getInstances();

    /**
     * Removes a component from the components container
     *
     * @param string $name
     *
     * @return static
     */
    public function remove($name);

    /**
     * Resolves the component based on its configuration
     *
     * @param string $name
     * @param array  $parameters
     *
     * @return mixed
     */
    public function make($name, $parameters = []);

    /**
     * Resolves a shared component based on their configuration
     *
     * @param string $name
     *
     * @return mixed
     */
    public function get($name);

    /**
     * @param object $target
     * @param string $property
     *
     * @return mixed
     */
    public function inject($target, $property);

    /**
     * Check whether the DI contains a component by a name
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name);

    /**
     * @param callable $callable
     * @param array    $parameters
     *
     * @return mixed
     */
    public function call($callable, $parameters = []);

    /**
     *Return the First DI created
     *
     * @return static
     */
    public static function getDefault();
}
