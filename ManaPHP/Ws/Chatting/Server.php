<?php

namespace ManaPHP\Ws\Chatting;

use ManaPHP\Component;
use ManaPHP\Coroutine;
use ManaPHP\Logging\Logger\LogCategorizable;

/**
 * @property-read \ManaPHP\Logging\LoggerInterface       $logger
 * @property-read \ManaPHP\Http\RequestInterface         $request
 * @property-read \ManaPHP\Identifying\IdentityInterface $identity
 * @property-read \ManaPHP\Ws\ServerInterface            $wsServer
 * @property-read \ManaPHP\Messaging\PubSubInterface     $pubSub
 */
class Server extends Component implements ServerInterface, LogCategorizable
{
    /**
     * @var string
     */
    protected $prefix = 'ws_chatting:';

    /**
     * @var bool
     */
    protected $dedicated = false;

    /**
     * @var array
     */
    protected $fds = [];

    /**
     * @var array[][]
     */
    protected $ids;

    /**
     * @var array[][]
     */
    protected $names;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['pubSub'])) {
            $this->injections['pubSub'] = $options['pubSub'];
        }

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

    /**
     * @param int    $fd
     * @param string $room
     */
    public function open($fd, $room = null)
    {
        $room = $room ?? $this->identity->getClaim('room_id');

        if (!$this->dedicated) {
            $this->fds[$fd] = true;
        }

        if (($id = $this->identity->getId('')) !== '') {
            $this->ids[$room][$id][$fd] = true;
        }

        if (($name = $this->identity->getName('')) !== '') {
            $this->names[$room][$name][$fd] = true;
        }

        $this->fireEvent('chatServer:come', compact('fd', 'id', 'name', 'room'));
    }

    /**
     * @param int    $fd
     * @param string $room
     */
    public function close($fd, $room = null)
    {
        $room = $room ?? $this->identity->getClaim('room_id');

        if (!$this->dedicated) {
            unset($this->fds[$fd]);
        }

        if (($id = $this->identity->getId('')) !== '') {
            unset($this->ids[$room][$id][$fd]);
            if (count($this->ids[$room][$id]) === 0) {
                unset($this->ids[$room][$id]);
            }
        }

        if (($name = $this->identity->getName('')) !== '') {
            unset($this->names[$room][$name][$fd]);
            if (count($this->names[$room][$name]) === 0) {
                unset($this->names[$room][$name]);
            }
        }

        $this->fireEvent('chatServer:leave', compact('fd', 'id', 'name', 'room'));
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
     * @param string $room
     * @param string $message
     */
    public function pushToRoom($room, $message)
    {
        $sent = [];

        foreach ($this->ids[$room] ?? [] as $id => $fds) {
            foreach ($fds as $fd => $_) {
                $sent[$fd] = true;
                $this->push($fd, $message);
            }
        }

        foreach ($this->names[$room] ?? [] as $name => $fds) {
            foreach ($fds as $fd => $_) {
                if (!isset($sent[$fd])) {
                    $sent[$fd] = true;
                    $this->push($fd, $message);
                }
            }
        }
    }

    /**
     * @param string $room
     * @param array  $receivers
     * @param string $message
     */
    public function pushToId($room, $receivers, $message)
    {
        foreach ($receivers as $id) {
            foreach ($this->ids[$room][$id] ?? [] as $fd => $_) {
                $this->push($fd, $message);
            }
        }
    }

    /**
     * @param string $room
     * @param array  $receivers
     * @param string $message
     */
    public function pushToName($room, $receivers, $message)
    {
        foreach ($receivers as $name) {
            foreach ($this->names[$room][$name] ?? [] as $fd => $_) {
                $this->push($fd, $message);
            }
        }
    }

    /**
     * @param string $message
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
     * @param string $room
     * @param string $message
     */
    public function closeRoom($room, $message)
    {
        $sent = [];
        foreach ($this->ids[$room] ?? [] as $id => $fds) {
            foreach ($fds as $fd => $_) {
                $sent[$fd] = true;
                $this->push($fd, $message);
            }
        }
        unset($this->ids[$room]);

        foreach ($this->names[$room] ?? [] as $name => $fds) {
            foreach ($fds as $fd => $_) {
                if (!isset($sent[$fd])) {
                    $sent[$fd] = true;
                    $this->push($fd, $message);
                }
            }
        }
        unset($this->names[$room]);
    }

    /**
     * @param string $room
     * @param array  $receivers
     * @param string $message
     */
    public function kickoutId($room, $receivers, $message)
    {
        $sent = [];

        foreach ($receivers as $id) {
            foreach ($this->ids[$room][$id] ?? [] as $fds) {
                foreach ($fds as $fd => $_) {
                    $sent[$fd] = true;
                    $this->push($fd, $message);
                }
            }
            unset($this->ids[$room][$id]);
        }

        foreach ($this->names[$room] ?? [] as $name => $fds) {
            foreach ($fds as $fd => $_) {
                if (isset($sent[$fd])) {
                    unset($this->names[$room][$name]);
                }
            }
        }
    }

    /**
     * @param string $room
     * @param array  $receivers
     * @param string $message
     */
    public function kickoutName($room, $receivers, $message)
    {
        $sent = [];

        foreach ($receivers as $name) {
            foreach ($this->names[$room][$name] ?? [] as $fds) {
                foreach ($fds as $fd => $_) {
                    $sent[$fd] = true;
                    $this->push($fd, $message);
                }
            }
            unset($this->names[$room][$name]);
        }

        foreach ($this->ids[$room] ?? [] as $id => $fds) {
            foreach ($fds as $fd => $_) {
                if (isset($sent[$fd])) {
                    unset($this->ids[$room][$id]);
                }
            }
        }
    }

    /**
     * @param string $type
     * @param string $room
     * @param array  $receivers
     * @param string $message
     */
    public function dispatch($type, $room, $receivers, $message)
    {
        if ($type === 'message.room') {
            $this->pushToRoom($room, $message);
        } elseif ($type === 'message.broadcast') {
            $this->broadcast($message);
        } elseif ($type === 'message.id') {
            $this->pushToId($room, $receivers, $message);
        } elseif ($type === 'message.name') {
            $this->pushToName($room, $receivers, $message);
        } elseif ($type === 'room.close') {
            $this->closeRoom($room, $message);
        } elseif ($type === 'kickout.id') {
            $this->kickoutId($room, $receivers, $message);
        } elseif ($type === 'kickout.name') {
            $this->kickoutName($room, $receivers, $message);
        } else {
            $this->logger->warn(
                ['unknown `:type` type message: :message', 'type' => $type, 'message' => $message],
                'chatServer.bad_type'
            );
        }
    }

    public function start()
    {
        Coroutine::create(
            function () {
                $this->pubSub->psubscribe(
                    [$this->prefix . '*'], function ($channel, $message) {
                    list($type, $room, $receivers) = explode(':', substr($channel, strlen($this->prefix)), 4);
                    if ($type !== null && $room !== null && $receivers !== null) {
                        $receivers = explode(',', $receivers);

                        $this->fireEvent('chatServer:pushing', compact('type', 'receivers', 'message'));
                        $this->dispatch($type, $room, $receivers, $message);
                        $this->fireEvent('chatServer:pushed', compact('type', 'receivers', 'message'));
                    } else {
                        $this->logger->warn($channel, 'chatServer.bad_channel');
                    }
                }
                );
            }
        );
    }
}