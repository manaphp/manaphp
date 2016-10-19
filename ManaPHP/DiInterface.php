<?php

namespace ManaPHP;

/**
 * ManaPHP\DiInterface initializer
 */
interface DiInterface
{

    /**
     * Registers a service in the service container
     *
     * @param string $name
     * @param mixed  $definition
     * @param bool   $shared
     * @param array  $aliases
     *
     * @return static
     */
    public function set($name, $definition, $shared = false, $aliases = []);

    /**
     * Registers an "always shared" service in the services container
     *
     * @param string $name
     * @param mixed  $definition
     * @param array  $aliases
     *
     * @return static
     */
    public function setShared($name, $definition, $aliases = []);

    /**
     * Removes a service from the service container
     *
     * @param string $name
     *
     * @return static
     */
    public function remove($name);

    /**
     * Resolves the service based on its configuration
     *
     * @param string $name
     * @param array  $parameters
     *
     * @return mixed
     */
    public function get($name, $parameters = []);

    /**
     * Resolves a shared service based on their configuration
     *
     * @param string $name
     * @param array  $parameters
     *
     * @return mixed
     */
    public function getShared($name, $parameters = []);

    /**
     * Check whether the DI contains a service by a name
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name);

    /**
     *Return the First DI created
     *
     * @return static
     */
    public static function getDefault();
}
