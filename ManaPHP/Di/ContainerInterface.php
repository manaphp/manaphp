<?php

namespace ManaPHP\Di;

interface ContainerInterface
{
    /**
     * @param string $id
     * @param mixed  $definition
     *
     * @return static
     */
    public function set($id, $definition);

    /**
     * @return array
     */
    public function getDefinitions();

    /**
     * @param string $id
     *
     * @return mixed
     */
    public function getDefinition($id);

    /**
     * @return array
     */
    public function getInstances();

    /**
     * @param string $id
     *
     * @return static
     */
    public function remove($id);

    /**
     * @param string $class
     * @param array  $parameters
     * @param string $id
     *
     * @return mixed
     */
    public function make($class, $parameters = [], $id = null);

    /**
     * @param string $id
     *
     * @return mixed
     */
    public function get($id);

    /**
     * @param object $target
     * @param string $property
     *
     * @return mixed
     */
    public function inject($target, $property);

    /**
     * @param string $id
     *
     * @return bool
     */
    public function has($id);

    /**
     * @param callable $callable
     * @param array    $parameters
     *
     * @return mixed
     */
    public function call($callable, $parameters = []);
}