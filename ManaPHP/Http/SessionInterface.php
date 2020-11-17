<?php

namespace ManaPHP\Http;

interface SessionInterface
{
    /**
     * Gets a session variable from an application context
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($name = null, $default = null);

    /**
     * Sets a session variable in an application context
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return static
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
     *
     * @return static
     */
    public function remove($name);

    /**
     * Destroys the active session or assigned session
     *
     * @param string $session_id
     *
     * @return static
     */
    public function destroy($session_id = null);

    /**
     * @return string
     */
    public function getId();

    /**
     * @param string $id
     *
     * @return static
     */
    public function setId($id);

    /**
     * @return string
     */
    public function getName();

    /**
     * @return int
     */
    public function getTtl();

    /**
     * @param int $ttl
     *
     * @return static
     */
    public function setTtl($ttl);

    /**
     * @param string $session_id
     *
     * @return array
     */
    public function read($session_id);

    /**
     * @param string $session_id
     * @param array  $data
     *
     * @return static
     */
    public function write($session_id, $data);
}