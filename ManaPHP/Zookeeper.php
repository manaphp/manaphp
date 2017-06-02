<?php

namespace ManaPHP;

use ManaPHP\ZooKeeper\Exception as ZookeeperException;
use ManaPHP\ZooKeeper\WatchedChildrenEvent;
use ManaPHP\ZooKeeper\WatchedDataEvent;

class ZooKeeper extends Component implements ZookeeperInterface
{
    /**
     * @var string
     */
    protected $_host;

    /**
     * @var int
     */
    protected $_sessionTimeout;

    /**
     * @var \Zookeeper
     */
    protected $_zookeeper;

    /**
     * @var array[]
     */
    protected $watchDataCallbacks = [];

    /**
     * @var array[]
     */
    protected $_watchChildrenCallbacks = [];

    /**
     * Create a handle to used communicate with zookeeper. * if the host is provided, attempt to connect.
     *
     * @param string   $host CSV list of host:port values (e.g. "host1:2181,host2:2181")
     * @param callable $watcher_cb
     * @param int      $sessionTimeout
     *
     * @throws \ZookeeperConnectionException when host is provided and when failed to connect to the host.
     */
    public function __construct($host, $watcher_cb = null, $sessionTimeout = 10000)
    {
        $this->_host = $host;

        $this->_zookeeper = new \ZooKeeper($host, $watcher_cb, $sessionTimeout);
        $this->_sessionTimeout = $this->getSessionTimeout();
    }

    /**
     * Create a node synchronously.
     *
     * @param string $path
     * @param string $value
     * @param array  $acl
     * @param int    $flags
     *
     * @return static
     * @throws \ZookeeperException
     * @throws \ManaPHP\ZooKeeper\Exception
     */
    public function create($path, $value = '', $acl = null, $flags = null)
    {
        $this->fireEvent('zookeeper:create', ['path' => $path, 'value' => $value, 'acl' => $acl, 'flags' => $flags]);

        if ($acl === null || $acl === []) {
            $acl = [['perms' => \Zookeeper::PERM_ALL, 'scheme' => 'world', 'id' => 'anyone']];
        }

        $parts = explode('/', ltrim($path, '/'));
        array_pop($parts);

        $parentPath = '';
        foreach ($parts as $part) {
            $parentPath .= '/' . $part;
            if (!$this->exists($parentPath)) {
                /** @noinspection NestedPositiveIfStatementsInspection */
                if (!$this->_zookeeper->create($parentPath, '', $acl)) {
                    /** @noinspection NotOptimalIfConditionsInspection */
                    /** @noinspection NestedPositiveIfStatementsInspection */
                    if (!$this->exists($parentPath)) {
                        throw new ZookeeperException('create `:path` path failed', ['path' => $parentPath]);
                    }
                }
            }
        }

        try {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $this->_zookeeper->create($path, $value, $acl, $flags);
        } catch (\ZookeeperNoNodeException $e) {
            throw new ZookeeperException('`:path` path is exists', ['path' => $path]);
        }

        return $this;
    }

    /**
     * Create a node synchronously, if it is not exists.
     *
     * @param string $path
     * @param string $value
     * @param array  $acl
     * @param int    $flags
     *
     * @return static
     * @throws \ZookeeperException
     * @throws \ManaPHP\ZooKeeper\Exception
     */
    public function createNx($path, $value = '', $acl = null, $flags = null)
    {
        if (!$this->exists($path)) {
            $this->create($path, $value, $acl, $flags);
        }

        return $this;
    }

    /**
     * Delete a node in zookeeper synchronously.
     *
     * @param string $path
     * @param int    $version
     *
     * @return static
     * @throws \ManaPHP\ZooKeeper\Exception
     */
    public function delete($path, $version = -1)
    {
        $this->fireEvent('zookeeper:delete', ['path' => $path, 'version' => $version]);

        if ($this->exists($path)) {
            $nodes = $this->getChildren($path);
            if (count($nodes) !== 0) {
                foreach ($nodes as $node) {
                    $this->delete($path . '/' . $node, -1);
                }
            }

            try {
                $this->_zookeeper->delete($path, $version);
            } catch (\ZookeeperNoNodeException $e) {
                return $this;
            }
        }

        return $this;
    }

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
    public function setData($path, $data, $version = -1, &$stat = null)
    {
        $this->fireEvent('zookeeper:setData', ['path' => $path, 'data' => $data, 'version' => $version]);

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $this->_zookeeper->set($path, $data, $version, $stat);

        return $this;
    }

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
    public function getData($path, $watcher_cb = null, &$stat = null, $max_size = 0)
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */

        try {
            $data = $this->_zookeeper->get($path, $watcher_cb, $stat, $max_size);
        } catch (\ZookeeperNoNodeException $e) {
            $data = false;
        }

        $this->fireEvent('zookeeper:getData', ['path' => $path, 'data' => $data, 'stat' => $stat]);

        return $data;
    }

    /**
     * Get children data of a path.
     *
     * @param string   $path
     * @param callable $watcher_cb
     *
     * @return array|false
     */
    public function getChildren($path, $watcher_cb = null)
    {
        try {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $children = $this->_zookeeper->getChildren($path, $watcher_cb);
        } catch (\ZookeeperNoNodeException $e) {
            $children = false;
        }

        $this->fireEvent('zookeeper:getChildren', ['path' => $path, 'children' => $children]);

        return $children;
    }

    /**
     * Checks the existence of a node in zookeeper synchronously.
     *
     * @param string   $path
     * @param callable $watcher_cb
     *
     * @return array|false
     */
    public function exists($path, $watcher_cb = null)
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        return $this->_zookeeper->exists($path, $watcher_cb);
    }

    /**
     * Gets the acl associated with a node synchronously.
     *
     * @param string $path
     *
     * @return array
     * @throws \ZookeeperException when connection not in connected status
     */
    public function getAcl($path)
    {
        return $this->_zookeeper->getAcl($path);
    }

    /**
     * Sets the acl associated with a node synchronously.
     *
     * @param string $path
     * @param int    $version
     * @param array  $acl
     *
     * @return bool
     * @throws \ZookeeperException when connection not in connected status
     */
    public function setAcl($path, $version, $acl)
    {
        $this->fireEvent('zookeeper:setAcl', ['path' => $path, 'version' => $version, 'acl' => $acl]);

        return $this->_zookeeper->setAcl($path, $version, $acl);
    }

    /**
     * return the client session id, only valid if the connections is currently connected
     * (ie. last watcher state is ZOO_CONNECTED_STATE)
     *
     * @return int
     * @throws \ZookeeperConnectionException when connection not in connected status
     */
    public function getSessionId()
    {
        return $this->_zookeeper->getClientId();
    }

    /**
     * Set a watcher function.
     *
     * @param callable $watcher_cb
     *
     * @return bool
     * @throws \ZookeeperConnectionException when connection not in connected status
     */
    public function setWatcher($watcher_cb)
    {
        return $this->_zookeeper->setWatcher($watcher_cb);
    }

    /**
     * Get the state of the zookeeper connection.
     *
     * @return int
     * @throws \ZookeeperConnectionException when connection not in connected status
     */
    public function getState()
    {
        return $this->_zookeeper->getState();
    }

    /**
     * Return the timeout for this session, only valid if the connections is currently connected
     * (ie. last watcher state is ZOO_CONNECTED_STATE). This value may change after a server reconnect.
     *
     * @return int
     * @throws \ZookeeperConnectionException when connection not in connected status
     */
    public function getSessionTimeout()
    {
        return $this->_zookeeper->getRecvTimeout();
    }

    /**
     * Specify application credentials.
     *
     * @param string   $scheme
     * @param string   $cert
     * @param callable $completion_cb
     *
     * @return bool
     */
    public function addAuth($scheme, $cert, $completion_cb = null)
    {
        return $this->_zookeeper->addAuth($scheme, $cert, $completion_cb);
    }

    /**
     * Checks if the current zookeeper connection state can be recovered.
     *
     * @return bool
     * @throws \ZookeeperConnectionException when connection not in connected status
     */
    public function isRecoverable()
    {
        return $this->_zookeeper->isRecoverable();
    }

    /**
     * Sets the stream to be used by the library for logging.
     *
     * @param resource $file
     *
     * @return bool
     */
    public function setLogStream($file)
    {
        return $this->_zookeeper->setLogStream($file);
    }

    /**
     * @param string|array $paths
     * @param callable     $callback
     * @param bool         $onlyOnce
     *
     * @return static
     * @throws \ZookeeperException
     * @throws \ManaPHP\Zookeeper\Exception
     */
    public function watchData($paths, $callback, $onlyOnce = false)
    {
        if (!is_callable($callback)) {
            throw new ZookeeperException('watch data failed: the callback of `:path` is not callable.', ['path' => implode(', ', (array)$paths)]);
        }

        foreach ((array)$paths as $name => $path) {
            if (!$this->exists($path)) {
                $this->create($path);
            }

            if (!isset($this->watchDataCallbacks[$path])) {
                $this->watchDataCallbacks[$path] = [];
            }

            $item = [$callback, $onlyOnce];
            if (is_string($name)) {
                $this->watchDataCallbacks[$path][$name] = $item;
            } else {
                $this->watchDataCallbacks[$path][] = $item;
            }

            $watchedDataEvent = new WatchedDataEvent();

            $watchedDataEvent->time = microtime(true);
            $watchedDataEvent->type = null;
            $watchedDataEvent->stat = $this->getState();
            $watchedDataEvent->path = $path;
            $watchedDataEvent->data = $this->getData($path, [$this, '_dataWatchCallback']);

            /** @noinspection VariableFunctionsUsageInspection */
            call_user_func($callback, $watchedDataEvent);
        }

        return $this;
    }

    /**
     * @param string $event
     * @param int    $stat
     * @param string $path
     *
     * @return bool|null
     */
    public function _dataWatchCallback($event, $stat, $path)
    {
        $watchedDataEvent = new WatchedDataEvent();

        $watchedDataEvent->time = microtime(true);
        $watchedDataEvent->type = $event;
        $watchedDataEvent->stat = $stat;
        $watchedDataEvent->path = $path;
        $watchedDataEvent->data = $this->getData($path, [$this, '_dataWatchCallback']);

        foreach ($this->watchDataCallbacks[$path] as $k => list($callback, $onlyOnce)) {
            /** @noinspection VariableFunctionsUsageInspection */
            $r = call_user_func($callback, $watchedDataEvent);
            if ($onlyOnce) {
                unset($this->watchDataCallbacks[$path][$k]);
            }

            if ($r === false) {
                return false;
            }
        }

        if (count($this->watchDataCallbacks[$path]) === 0) {
            unset($this->watchDataCallbacks[$path]);

            $this->getData($path);
        }

        return null;
    }

    /**
     * @param string $path
     * @param string $name
     *
     * @throws \ZookeeperException
     */
    public function cancelWatch($path, $name)
    {
        if (isset($this->watchDataCallbacks[$path][$name])) {
            unset($this->watchDataCallbacks[$path][$name]);
            if (count($this->watchDataCallbacks[$path]) === 0) {
                unset($this->watchDataCallbacks[$path]);
                $this->getData($path);
            }
        }
    }

    /**
     * @param string|array $paths
     * @param callable     $callback
     * @param bool         $onlyOnce
     *
     * @return static
     * @throws \ZookeeperException
     * @throws \ManaPHP\Zookeeper\Exception
     */
    public function watchChildren($paths, $callback, $onlyOnce = false)
    {
        if (!is_callable($callback)) {
            throw new ZookeeperException('watch children failed: the callback of `:path` is not callable.', ['path' => implode(', ', (array)$paths)]);
        }

        foreach ((array)$paths as $name => $path) {
            if (!$this->exists($path)) {
                $this->create($path);
            }

            if (!isset($this->_watchChildrenCallbacks[$path])) {
                $this->_watchChildrenCallbacks[$path] = [];
            }

            $item = [$callback, $onlyOnce];
            if (is_string($name)) {
                if (isset($this->_watchChildrenCallbacks[$path][$name])) {
                    continue;
                }
                $this->_watchChildrenCallbacks[$path][$name] = $item;
            } else {
                $this->_watchChildrenCallbacks[$path][] = $item;
            }

            $watchedChildrenEvent = new WatchedChildrenEvent();

            $watchedChildrenEvent->time = microtime(true);
            $watchedChildrenEvent->type = null;
            $watchedChildrenEvent->stat = null;
            $watchedChildrenEvent->path = $path;
            $watchedChildrenEvent->children = $this->getChildren($path, [$this, '_watchChildrenCallback']);

            /** @noinspection VariableFunctionsUsageInspection */
            call_user_func($callback, $watchedChildrenEvent);
        }

        return $this;
    }

    /**
     * @param string $event
     * @param int    $stat
     * @param string $path
     *
     * @return bool|null
     */
    public function _watchChildrenCallback($event, $stat, $path)
    {
        $watchedChildrenEvent = new WatchedChildrenEvent();

        $watchedChildrenEvent->time = microtime(true);
        $watchedChildrenEvent->type = $event;
        $watchedChildrenEvent->stat = $stat;
        $watchedChildrenEvent->path = $path;
        $watchedChildrenEvent->children = $this->getChildren($path, [$this, '_watchChildrenCallback']);

        foreach ($this->_watchChildrenCallbacks[$path] as $k => list($callback, $onlyOnce)) {
            /** @noinspection VariableFunctionsUsageInspection */
            $r = call_user_func($callback, $watchedChildrenEvent);
            if ($onlyOnce) {
                unset($this->_watchChildrenCallbacks[$path][$k]);
            }

            if ($r === false) {
                return false;
            }
        }

        if (count($this->_watchChildrenCallbacks[$path]) === 0) {
            unset($this->_watchChildrenCallbacks[$path]);

            $this->getChildren($path);
        }

        return null;
    }

    /**
     * Sets the debugging level for the library.
     *
     * @param int $level
     *
     * @return bool
     */
    public static function setDebugLevel($level)
    {
        return \Zookeeper::setDebugLevel($level);
    }

    /**
     * Enable/disable quorum endpoint order randomization.
     *
     * @param bool $trueOrFalse
     *
     * @return bool
     */
    public static function setDeterministicConnOrder($trueOrFalse)
    {
        return \Zookeeper::setDeterministicConnOrder($trueOrFalse);
    }
}