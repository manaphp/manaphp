<?php

namespace ManaPHP\Http;

/**
 * Interface ManaPHP\Http\SessionInterface
 *
 * @package session
 */
interface SessionInterface
{
    /**
     * @return static
     */
    public function start();

    /**
     * Gets a session variable from an application context
     *
     * @param string $name
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function get($name = null, $defaultValue = null);

    /**
     * Sets a session variable in an application context
     *
     * @param string $name
     * @param mixed  $value
     */
    public function set($name, $value);

    /**
     * Check whether a session variable is set in an application context
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name);

    /**
     * Removes a session variable from an application context
     *
     * @param string $name
     */
    public function remove($name);

    /**
     * Destroys the active session
     *
     * @return void
     */
    public function destroy();

    /**
     * @return string
     */
    public function getId();

    /**
     * @param string $id
     *
     * @return void
     */
    public function setId($id);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $name
     *
     * @return string
     */
    public function setName($name);

    /**
     * @return void
     */
    public function clean();
}