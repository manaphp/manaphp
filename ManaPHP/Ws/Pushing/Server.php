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

        if (isset($options['sso'])) {
            $this->_sso = (bool)$options['sso'];
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

    public function close($fd)
    {
        if ($this->_shared) {
            unset($this->_fds[$fd]);
        }

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
     *
     * @return void
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
     *
     * @return void
     */
    public function pushToRole($receivers, $message)
    {
        $users = $this->_users;

        if (str_contains($receivers, ',')) {
            $_receivers = explode(',', $receivers);
            foreach ($users as $user) {
                $role = $user['role'];

                foreach ($_receivers as $receiver) {
                    if ($role === $receiver
                        || (str_contains($role, $receiver) && preg_match("#\\b$receiver\\b#", $role) === 1)
                    ) {
                        $this->push($user['fd'], $message);
                        break;
                    }
                }
            }
        } else {
            foreach ($users as $user) {
                $role = $user['role'];
                if ($role === $receivers
                    || (str_contains($role, $receivers) && preg_match("#\\b$receivers\\b#", $role) === 1)
                ) {
                    $this->push($user['fd'], $message);
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
        foreach ($this->_users as $user) {
            $this->push($user['fd'], $message);
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
     * @param string $receivers
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
                $this->pubSub->psubscribe(
                    [$this->_prefix . $this->_endpoint . ':*'], function ($channel, $data) {
                    if (($pos = strrpos($channel, ':')) !== false) {
                        $type = substr($channel, $pos + 1);

                        $pos = strpos($data, ':');
                        $receivers = substr($data, 0, $pos);
                        $message = substr($data, $pos + 1);
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
