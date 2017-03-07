<?php
namespace ManaPHP\Http\Session;

/**
 * Interface ManaPHP\Http\Session\AdapterInterface
 *
 * @package session
 */
interface AdapterInterface
{
    /**
     * The open callback works like a constructor in classes and is executed when the session is being opened.
     * It is the first callback function executed when the session is started automatically or manually with session_start().
     * Return value is TRUE for success, FALSE for failure.
     *
     * @param string $savePath
     * @param string $sessionName
     *
     * @return bool
     */
    public function open($savePath, $sessionName);

    /**
     * The close callback works like a destructor in classes and is executed after the session write callback has been called.
     * It is also invoked when session_write_close() is called. Return value should be TRUE for success, FALSE for failure.
     *
     * @return bool
     */
    public function close();

    /**
     * The read callback must always return a session encoded (serialized) string, or an empty string if there is no data to read.
     *
     * @param string $sessionId
     *
     * @return string
     */
    public function read($sessionId);

    /**
     * The write callback is called when the session needs to be saved and closed.
     * The serialized session data passed to this callback should be stored against the passed session ID.
     * When retrieving this data, the read callback must return the exact value that was originally passed to the write callback.
     *
     * @param string $sessionId
     * @param string $data
     *
     * @return string
     */
    public function write($sessionId, $data);

    /**
     * This callback is executed when a session is destroyed with session_destroy()
     * or with session_regenerate_id() with the destroy parameter set to TRUE.
     * Return value should be TRUE for success, FALSE for failure.
     *
     * @param string $sessionId
     *
     * @return bool
     */
    public function destroy($sessionId);

    /**
     * The value of lifetime which is passed to this callback can be set in session.gc_maxlifetime.
     * Return value should be TRUE for success, FALSE for failure.
     *
     * @param int $ttl
     *
     * @return bool
     */
    public function gc($ttl);

    /**
     * @return void
     */
    public function clean();
}