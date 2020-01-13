<?php
namespace ManaPHP\WebSocket\Processes;

use ManaPHP\Event\EventArgs;
use ManaPHP\Process;
use Swoole\Table;

/**
 * Class SubscriberProcess
 * @package App\Processes
 * @property-read \ManaPHP\WebSocket\ServerInterface $wsServer
 */
class PusherProcess extends Process
{
    /**
     * @var array
     */
    protected $_endpoint;

    /**
     * @var string
     */
    protected $_prefix = 'ws:pusher:';

    /**
     * @var int
     */
    protected $_capacity = 10000;

    /**
     * @var bool
     */
    protected $_sso = false;

    /**
     * @var \swoole_table
     */
    protected $_name2id;

    /**
     * @var \swoole_table
     */
    protected $_users = [];

    /**
     * PusherProcess constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->_endpoint = $options['endpoint'];

        if (isset($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }

        if (isset($options['capacity'])) {
            $this->_capacity = (int)$options['capacity'];
        }

        if (isset($options['sso'])) {
            $this->_sso = (bool)$options['sso'];
        }

        $table = new Table($this->_capacity);
        $table->column('fd', Table::TYPE_INT);
        $table->column('id', Table::TYPE_INT);
        $table->column('name', Table::TYPE_STRING, 32);
        $table->column('role', Table::TYPE_STRING, 64);
        $table->create();

        $this->_users = $table;
        $this->attachEvent('ws:open', [$this, 'onWsOpen']);
        $this->attachEvent('ws:close', [$this, 'onWsClose']);
    }

    public function onWsOpen(EventArgs $eventArgs)
    {
        $fd = $eventArgs->data;

        $identity = $this->identity;
        if (!$id = $identity->getId('')) {
            return;
        }

        $user = ['fd' => $fd, 'id' => $id, 'name' => $identity->getName(), 'role' => $identity->getRole()];

        if ($this->_sso) {
            $this->_users->set($id, $user);
            $this->_name2id->set($user['name'], ['id' => $id]);
        } else {
            $this->_users->set($fd, $user);
        }
    }

    public function onWsClose(EventArgs $eventArgs)
    {
        $fd = $eventArgs->data;

        if (!$id = $this->identity->getId('')) {
            return;
        }

        if ($this->_sso) {
            $this->_users->del($id);
            $this->_name2id->del($this->identity->getName());
        } else {
            $this->_users->del($fd);
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
            if (strpos($receivers, ',') === false) {
                if ($user = $users->get($receivers)) {
                    $this->push($user['fd'], $message);
                }
            } else {
                foreach (explode(',', $receivers) as $user_id) {
                    if ($user = $users->get($user_id)) {
                        $this->push($user['fd'], $message);
                    }
                }
            }
        } else {
            if (strpos($receivers, ',') === false) {
                $user_id = (int)$receivers;
                foreach ($users as $user) {
                    if ($user['id'] === $user_id) {
                        $this->push($user['fd'], $message);
                    }
                }
            } else {
                foreach ($users as $user) {
                    $user_id = (string)$user['id'];
                    if (strpos($receivers, $user_id) !== false && preg_match("#\\b$user_id\\b#", $receivers) === 1) {
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
    public function pushName($receivers, $message)
    {
        $users = $this->_users;

        if ($this->_sso) {
            $name2id = $this->_name2id;

            if (strpos($receivers, ',') === false) {
                if ($name = $name2id->get($receivers)) {
                    $user_id = $name['id'];
                    if (isset($users[$user_id])) {
                        $this->push($users[$user_id]['fd'], $message);
                    }
                }
            } else {
                foreach (explode(',', $receivers) as $user_name) {
                    if ($name = $name2id->get($user_name)) {
                        $user_id = $name['id'];
                        if (isset($users[$user_id])) {
                            $this->push($users[$user_id]['fd'], $message);
                        }
                    }
                }
            }
        } else {
            if (strpos(',', $receivers) === false) {
                foreach ($users as $user) {
                    if ($user['name'] === $receivers) {
                        $this->push($user['fd'], $message);
                    }
                }
            } else {
                foreach ($users as $user) {
                    $user_name = $user['name'];
                    if (strpos($receivers, $user_name) !== false && preg_match('#\b' . preg_quote($user_name, '#') . '\b#', $receivers) === 1) {
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
    public function pushRole($receivers, $message)
    {
        $users = $this->_users;

        if (strpos(',', $receivers) === false) {
            foreach ($users as $user) {
                $role = $user['role'];
                if ($role === $receivers || (strpos($role, $receivers) !== false && preg_match('#\b' . preg_quote($receivers, '#') . '\b#', $role) === 1)) {
                    $this->push($user['fd'], $message);
                }
            }
        } else {
            $roles = explode(',', $receivers);
            foreach ($users as $user) {
                $current_role = $user['role'];

                foreach ($roles as $role) {
                    if ($current_role === $role || (strpos($current_role, $role) !== false && preg_match('#b' . preg_quote($role, '#') . '\b#',
                                $current_role) === 1)) {
                        $this->push($user['fd'], $message);
                        break;
                    }
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
        $this->wsServer->broadcast($message);
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
            $this->pushName($receivers, $message);
        } elseif ($type === 'role') {
            $this->pushRole($receivers, $message);
        } else {
            $this->logger->warn(['unknown `:type` type message: :message', 'type' => $type, 'message' => $message], 'wsPusher.bad_type');
        }
    }

    public function run()
    {
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
