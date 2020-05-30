<?php

namespace ManaPHP\Plugins;

use ManaPHP\Event\EventArgs;
use ManaPHP\Plugin;

/**
 * Class WsPusherPlugin
 *
 * @package ManaPHP\Plugins
 *
 * @property-read \ManaPHP\WebSocket\ServerInterface $wsServer
 */
class  WsPusherPlugin extends Plugin
{
    /**
     * @var array
     */
    protected $_endpoint;

    /**
     * @var string
     */
    protected $_prefix = 'broker:pusher:';

    /**
     * @var bool
     */
    protected $_sso = false;

    /**
     * @var array
     */
    protected $_name2id = [];

    /**
     * @var array
     */
    protected $_users = [];

    /**
     * @var int
     */
    protected $_worker_id;

    /**
     * WsPusherPlugin constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->_endpoint = $options['endpoint'];

        if (isset($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }

        if (isset($options['sso'])) {
            $this->_sso = (bool)$options['sso'];
        }

        $this->attachEvent('ws:start', [$this, 'onWsStart']);
        $this->attachEvent('ws:open', [$this, 'onWsOpen']);
        $this->attachEvent('ws:close', [$this, 'onWsClose']);
    }

    public function onWsOpen(EventArgs $eventArgs)
    {
        $fd = $eventArgs->data;

        if (!$id = $this->identity->getId('')) {
            return;
        }

        if ($this->_sso && $user = $this->_users[$id] ?? false) {
            $this->wsServer->disconnect($user['fd']);
        }

        $name = $this->identity->getName();
        $user = ['fd' => $fd, 'id' => $id, 'name' => $name, 'role' => $this->identity->getRole()];

        if ($this->_sso) {
            $this->_name2id[$name] = $id;
            $this->_users[$id] = $user;
        } else {
            $this->_users[$fd] = $user;
        }
    }

    public function onWsClose(EventArgs $eventArgs)
    {
        $fd = $eventArgs->data;

        if (!$id = $this->identity->getId('')) {
            return;
        }

        if ($this->_sso) {
            unset($this->_users[$id], $this->_name2id[$this->identity->getName()]);
        } else {
            unset($this->_users[$fd]);
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
     * @param string $receivers
     * @param string $message
     *
     * @return void
     */
    public function pushToId($receivers, $message)
    {
        $users = $this->_users;

        if ($this->_sso) {
            if (str_contains($receivers, ',')) {
                foreach (explode(',', $receivers) as $id) {
                    if ($user = $users[$id] ?? false) {
                        $this->push($user['fd'], $message);
                    }
                }
            } else {
                if ($user = $users[$receivers] ?? false) {
                    $this->push($user['fd'], $message);
                }
            }
        } else {
            if (str_contains($receivers, ',')) {
                foreach ($users as $user) {
                    $id = (string)$user['id'];
                    if (str_contains($receivers, $id) && preg_match("#\\b$id\\b#", $receivers) === 1) {
                        $this->push($user['fd'], $message);
                    }
                }
            } else {
                $id = (int)$receivers;
                foreach ($users as $user) {
                    if ($user['id'] === $id) {
                        $this->push($user['fd'], $message);
                    }
                }
            }
        }
    }

    /**
     * @param string $receivers
     * @param string $message
     */
    public function pushToName($receivers, $message)
    {
        $users = $this->_users;

        if ($this->_sso) {
            if (str_contains($receivers, ',')) {
                foreach (explode(',', $receivers) as $name) {
                    if ($id = $this->_name2id[$name] ?? false) {
                        $this->push($users[$id]['fd'], $message);
                    }
                }
            } else {
                if ($id = $this->_name2id[$receivers] ?? false) {
                    $this->push($users[$id]['fd'], $message);
                }
            }
        } else {
            if (str_contains($receivers, ',')) {
                foreach ($users as $user) {
                    $name = $user['name'];
                    if (str_contains($receivers, $name) && preg_match("#\\b$name\\b#", $receivers) === 1) {
                        $this->push($user['fd'], $message);
                    }
                }
            } else {
                foreach ($users as $user) {
                    if ($user['name'] === $receivers) {
                        $this->push($user['fd'], $message);
                    }
                }
            }
        }
    }

    /**
     * @param string $receivers
     * @param string $message
     */
    public function pushToRole($receivers, $message)
    {
        $users = $this->_users;

        if (str_contains($receivers, ',')) {
            $receivers = explode(',', $receivers);
            foreach ($users as $user) {
                $role = $user['role'];

                foreach ($receivers as $receiver) {
                    if ($role === $receiver || (str_contains($role, $receiver) && preg_match("#\\b$receiver\\b#", $role) === 1)) {
                        $this->push($user['fd'], $message);
                        break;
                    }
                }
            }
        } else {
            foreach ($users as $user) {
                $role = $user['role'];
                if ($role === $receivers || (str_contains($role, $receivers) && preg_match("#\\b$receivers\\b#", $role) === 1)) {
                    $this->push($user['fd'], $message);
                }
            }
        }
    }

    public function pushToAll($message)
    {
        foreach ($this->_users as $user) {
            $this->push($user['fd'], $message);
        }
    }

    public function broadcast($message)
    {
        if ($this->_worker_id === 0) {
            $this->wsServer->broadcast($message);
        }
    }

    /**
     * @param string $type
     * @param string $receivers
     * @param string $message
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
            $this->logger->warn(['unknown `:type` type message: :message', 'type' => $type, 'message' => $message], 'wsPusher.bad_type');
        }
    }

    public function onWsStart(EventArgs $eventArgs)
    {
        $this->_worker_id = $eventArgs->data;

        $this->pubSub->psubscribe([$this->_prefix . $this->_endpoint . ':*'], function ($channel, $data) {
            if (($pos = strrpos($channel, ':')) !== false) {
                $type = substr($channel, $pos + 1);

                $pos = strpos($data, ':');
                $receivers = substr($data, 0, $pos);
                $message = substr($data, $pos + 1);
                $this->logger->debug(compact('type', 'receivers', 'message'));
                $this->dispatch($type, $receivers, $message);
            } else {
                $this->logger->warn($channel, 'wsPusher.bad_channel');
            }
        });
    }
}
