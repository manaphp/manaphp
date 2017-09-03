<?php
namespace ManaPHP;

interface ZookeeperInterface
{
    /**
     * Create a node synchronously.
     *
     * @param string $path
     * @param string $value
     * @param array  $acl
     * @param int    $flags
     *
     * @return static
     */
    public function create($path, $value = '', $acl = null, $flags = null);

    /**
     * Create a node synchronously, if it is not exists.
     *
     * @param string $path
     * @param string $value
     * @param array  $acl
     * @param int    $flags
     *
     * @return static
     */
    public function createNx($path, $value = '', $acl = null, $flags = null);

    /**
     * Delete a node in zookeeper synchronously.
     *
     * @param string $path
     * @param int    $version
     *
     * @return static
     */
    public function delete($path, $version = -1);

    /**
     * Sets the data associated with a node. If the node doesn't exist yet, it is created.
     *
     * @param string $path
     * @param string $data
     * @param int    $version
     * @param array  $stat
     *
     * @return static
     */
    public function setData($path, $data, $version = -1, &$stat = null);

    /**
     * Gets the data associated with a node synchronously.
     *
     * @param string   $path
     * @param callable $watcher_cb
     * @param array    $stat
     * @param int      $max_size
     *
     * @return string|false
     */
    public function getData($path, $watcher_cb = null, &$stat = null, $max_size = 0);

    /**
     * Get children data of a path.
     *
     * @param string   $path
     * @param callable $watcher_cb
     *
     * @return array|false
     */
    public function getChildren($path, $watcher_cb = null);

    /**
     * Checks the existence of a node in zookeeper synchronously.
     *
     * @param string   $path
     * @param callable $watcher_cb
     *
     * @return array|false
     */
    public function exists($path, $watcher_cb = null);

    /**
     * Gets the acl associated with a node synchronously.
     *
     * @param string $path
     *
     * @return array
     */
    public function getAcl($path);

    /**
     * Sets the acl associated with a node synchronously.
     *
     * @param string $path
     * @param int    $version
     * @param array  $acl
     *
     * @return bool
     */
    public function setAcl($path, $version, $acl);

    /**
     * return the client session id, only valid if the connections is currently connected
     * (ie. last watcher state is ZOO_CONNECTED_STATE)
     *
     * @return int
     */
    public function getSessionId();

    /**
     * @param string|array $paths
     * @param callable     $callback
     * @param bool         $onlyOnce
     *
     * @return static
     */
    public function watchData($paths, $callback, $onlyOnce = false);

    /**
     * @param string|array $paths
     * @param callable     $callback
     * @param bool         $onlyOnce
     *
     * @return static
     */
    public function watchChildren($paths, $callback, $onlyOnce = false);
}