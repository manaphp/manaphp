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
     * @param string $name
     *
     * @return static
     */
    public function remove($name);

    /**
     * @param string $class
     * @param array  $parameters
     *
     * @return mixed
     */
    public function make($class, $parameters = []);

    /**
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
     * @return static
     */
    public static function getDefault();
}
