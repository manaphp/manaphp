<?php

namespace ManaPHP\Http;

/**
 * ManaPHP\Http\Session\AdapterInterface initializer
 */
interface SessionInterface
{

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
    public function getSessionId();
}