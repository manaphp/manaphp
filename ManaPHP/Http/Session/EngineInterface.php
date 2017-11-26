<?php
namespace ManaPHP\Http\Session;

/**
 * Interface ManaPHP\Http\Session\AdapterInterface
 *
 * @package session
 */
interface EngineInterface
{
    /**
     * The read must always return a session encoded (serialized) string, or an empty string if there is no data to read.
     *
     * @param string $session_id
     *
     * @return string
     */
    public function read($session_id);

    /**
     * The write is called when the session needs to be saved and closed.
     *
     * @param string $session_id
     * @param string $data
     * @param array  $context
     *
     * @return bool
     */
    public function write($session_id, $data, $context);

    /**
     * executed when a session is destroyed with session_destroy()
     * or with session_regenerate_id() with the destroy parameter set to TRUE.
     *
     * @param string $session_id
     *
     * @return bool
     */
    public function destroy($session_id);

    /**
     * @param int $ttl
     *
     * @return bool
     */
    public function gc($ttl);
}