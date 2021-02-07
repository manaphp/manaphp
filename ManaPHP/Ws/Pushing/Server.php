<?php

namespace ManaPHP\Ws\Pushing;

use ManaPHP\Component;
use ManaPHP\Coroutine;
use ManaPHP\Logging\Logger\LogCategorizable;

/**
 * @property-read \ManaPHP\Ws\ServerInterface    $wsServer
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
class Server extends Component implements ServerInterface, LogCategorizable
{
    /**
     * @var array
     */
    protected $endpoint;

    /**
     * @var string
     */
    protected $prefix = 'ws_pushing:';

    /**
     * @var bool
     */
    protected $dedicated = false;

    /**
     * @var true[][]
     */
    protected $ids;

    /**
     * @var true[][]
     */
    protected $names;

    /**
     * @var true[][]
     */
    protected $rooms;

    /**
     * @var true[][]
     */
    protected $roles;

    /**
     * @var array
     */
    protected $fds = [];

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['pubSub'])) {
            $this->injections['pubSub'] = $options['pubSub'];
        }

        $this->endpoint = $options['endpoint'];

        if (isset($options['prefix'])) {
            $this->prefix = $options['prefix'];
        }

        if (isset($options['dedicated'])) {
            $this->dedicated = (bool)$options['dedicated'];
        }
    }

    public function categorizeLog()
    {
        return str_replace('\\', '.', get_class($this));
    }

    public function open($fd)
    {
        if (!$this->dedicated) {
            $this->fds[$fd] = true;
        }

        if (($id = $this->identity->getId('')) !== '') {
            $this->ids[$id][$fd] = true;
        }

        if (($name = $this->identity->getName('')) !== '') {
            $this->names[$name][$fd] = true;
        }

        if (($room = $this->request->get('room_id', '')) !== '') {
            $this->rooms[$room][$fd] = true;
        }

        if (($role = $this->identity->getRole('')) !== '') {
            foreach (explode(',', $role) as $r) {
                $this->roles[$r][$fd] = true;
            }
        }
    }

    public function close($fd)
    {
        if (!$this->dedicated) {
            unset($this->fds[$fd]);
        }

        if (($id = $this->identity->getId('')) !== '') {
            unset($this->ids[$id][$fd]);
            if (count($this->ids[$id]) === 0) {
                unset($this->ids[$id]);
            }
        }

        if (($name = $this->identity->getName('')) !== '') {
            unset($this->names[$name][$fd]);
            if (count($this->names[$name]) === 0) {
                unset($this->names[$name]);
            }
        }

        if (($room = $this->request->get('room_id', '')) !== '') {
            unset($this->rooms[$room][$fd]);
            if (count($this->rooms[$room]) === 0) {
                unset($this->rooms[$room]);
            }
        }

        if (($role = $this->identity->getRole('')) !== '') {
            foreach (explode(',', $role) as $r) {
                unset($this->roles[$r][$fd]);
                if (count($this->roles[$r]) === 0) {
                    unset($this->roles[$r]);
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
            foreach ($this->ids[$id] ?? [] as $fd => $_) {
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
            foreach ($this->names[$name] ?? [] as $fd => $_) {
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
    public function pushToRoom($receivers, $message)
    {
        foreach ($receivers as $room) {
            foreach ($this->rooms[$room] ?? [] as $fd => $_) {
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
        $sent = [];
        foreach ($receivers as $role) {
            foreach ($this->roles[$role] ?? [] as $fd => $_) {
                if (!isset($sent[$fd])) {
                    $this->push($fd, $message);
                    $sent[$fd] = true;
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
        foreach ($this->ids as $id => $fds) {
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
        if ($this->dedicated) {
            $this->wsServer->broadcast($message);
        } else {
            foreach ($this->fds as $fd => $_) {
                $this->push($fd, $message);
            }
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
        } elseif ($type === 'room') {
            $this->pushToRoom($receivers, $message);
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
                $prefix = $this->prefix . $this->endpoint;
                $this->pubSub->psubscribe(
                    ["$prefix:*"], function ($channel, $message) use ($prefix) {
                    list($type, $receivers) = explode(':', substr($channel, strlen($prefix) + 1), 2);

                    if ($type !== null && $receivers !== null) {
                        $receivers = explode(',', $receivers);

                        $this->fireEvent('wspServer:pushing', compact('type', 'receivers', 'message'));
                        $this->dispatch($type, $receivers, $message);
                        $this->fireEvent('wspServer:pushed', compact('type', 'receivers', 'message'));
                    } else {
                        $this->logger->warn($channel, 'wspServer.bad_channel');
                    }
                }
                );
            }
        );
    }
}
