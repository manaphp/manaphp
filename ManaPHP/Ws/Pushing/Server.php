<?php

namespace ManaPHP\Ws\Pushing;

use ManaPHP\Component;
use ManaPHP\Coroutine;
use ManaPHP\Logging\Logger\LogCategorizable;

/**
 * @property-read \ManaPHP\Ws\ServerInterface $wsServer
 */
class Server extends Component implements ServerInterface, LogCategorizable
{
    /**
     * @var array
     */
    protected $_endpoint;

    /**
     * @var string
     */
    protected $_prefix = 'ws_pushing:';

    /**
     * @var bool
     */
    protected $_shared = true;

    /**
     * @var true[][]
     */
    protected $_id_fds;

    /**
     * @var true[][]
     */
    protected $_name_fds;

    /**
     * @var true[][]
     */
    protected $_role_fds;

    /**
     * @var array
     */
    protected $_fds = [];

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['pubSub'])) {
            $this->_injections['pubSub'] = $options['pubSub'];
        }

        $this->_endpoint = $options['endpoint'];

        if (isset($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }

        if (isset($options['shared'])) {
            $this->_shared = (bool)$options['shared'];
        }
    }

    public function categorizeLog()
    {
        return str_replace('\\', '.', get_class($this));
    }

    public function open($fd)
    {
        if ($this->_shared) {
            $this->_fds[$fd] = true;
        }

        if (!$id = $this->identity->getId('')) {
            return;
        }

        $this->_id_fds[$id][$fd] = true;

        if (($name = $this->identity->getName('')) !== '') {
            $this->_name_fds[$name][$fd] = true;
        }

        if (($role = $this->identity->getRole('')) !== '') {
            foreach (explode(',', $role) as $r) {
                $this->_role_fds[$r][$fd] = true;
            }
        }
    }

    public function close($fd)
    {
        if ($this->_shared) {
            unset($this->_fds[$fd]);
        }

        if (!$id = $this->identity->getId('')) {
            return;
        }

        unset($this->_id_fds[$id][$fd]);
        if (count($this->_id_fds[$id]) === 0) {
            unset($this->_id_fds[$id]);
        }

        if (($name = $this->identity->getName('')) !== '') {
            unset($this->_name_fds[$name][$fd]);
            if (count($this->_name_fds[$name]) === 0) {
                unset($this->_name_fds[$name]);
            }
        }

        if (($role = $this->identity->getRole('')) !== '') {
            foreach (explode(',', $role) as $r) {
                unset($this->_role_fds[$r][$fd]);
                if (count($this->_role_fds[$r]) === 0) {
                    unset($this->_role_fds[$r]);
                }
            }
        }
    }

    /**
     * @param int    $fd
     * @param string $message
     *
     * @return void
     */
    public function push($fd, $message)
    {
        $this->wsServer->push($fd, $message);
    }

    /**
     * @param array  $receivers
     * @param string $message
     *
     * @return void
     */
    public function pushToId($receivers, $message)
    {
        foreach ($receivers as $id) {
            foreach ($this->_id_fds[$id] ?? [] as $fd => $_) {
                $this->push($fd, $message);
            }
        }
    }

    /**
     * @param array  $receivers
     * @param string $message
     *
     * @return void
     */
    public function pushToName($receivers, $message)
    {
        foreach ($receivers as $name) {
            foreach ($this->_name_fds[$name] ?? [] as $fd => $_) {
                $this->push($fd, $message);
            }
        }
    }

    /**
     * @param array  $receivers
     * @param string $message
     *
     * @return void
     */
    public function pushToRole($receivers, $message)
    {
        $fds = [];
        foreach ($receivers as $role) {
            foreach ($this->_role_fds[$role] ?? [] as $fd => $_) {
                if (!isset($fds[$fd])) {
                    $this->push($fd, $message);
                    $fds[$fd] = true;
                }
            }
        }
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public function pushToAll($message)
    {
        foreach ($this->_id_fds as $fds) {
            foreach ($fds as $fd => $_) {
                $this->push($fd, $message);
            }
        }
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public function broadcast($message)
    {
        if ($this->_shared) {
            foreach ($this->_fds as $fd => $_) {
                $this->push($fd, $message);
            }
        } else {
            $this->wsServer->broadcast($message);
        }
    }

    /**
     * @param string $type
     * @param array  $receivers
     * @param string $message
     *
     * @return void
     */
    public function dispatch($type, $receivers, $message)
    {
        if ($type === 'broadcast') {
            $this->broadcast($message);
        } elseif ($type === 'all') {
            $this->pushToAll($message);
        } elseif ($type === 'id') {
            $this->pushToId($receivers, $message);
        } elseif ($type === 'name') {
            $this->pushToName($receivers, $message);
        } elseif ($type === 'role') {
            $this->pushToRole($receivers, $message);
        } else {
            $this->logger->warn(
                ['unknown `:type` type message: :message', 'type' => $type, 'message' => $message],
                'wspServer.bad_type'
            );
        }
    }

    public function start()
    {
        Coroutine::create(
            function () {
                $prefix = $this->_prefix . $this->_endpoint;
                $this->pubSub->psubscribe(
                    ["$prefix:*"], function ($channel, $message) use ($prefix) {
                    list($type, $receivers) = explode(':', substr($channel, strlen($prefix) + 1), 2);

                    if ($type !== null && $receivers !== null) {
                        $receivers = explode(',', $receivers);
                        $this->logger->debug(compact('type', 'receivers', 'message'));
                        $this->dispatch($type, $receivers, $message);
                    } else {
                        $this->logger->warn($channel, 'wspServer.bad_channel');
                    }
                }
                );
            }
        );
    }
}
