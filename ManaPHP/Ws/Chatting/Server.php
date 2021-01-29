<?php

namespace ManaPHP\Ws\Chatting;

use ManaPHP\Component;
use ManaPHP\Coroutine;
use ManaPHP\Logging\Logger\LogCategorizable;

/**
 * @property-read \ManaPHP\Http\RequestInterface         $request
 * @property-read \ManaPHP\Ws\ServerInterface            $wsServer
 * @property-read \ManaPHP\Identifying\IdentityInterface $identity
 */
class Server extends Component implements ServerInterface, LogCategorizable
{
    /**
     * @var string
     */
    protected $_prefix = 'ws_chatting:';

    /**
     * @var bool
     */
    protected $_dedicated = false;

    /**
     * @var array
     */
    protected $_fds = [];

    /**
     * @var array[][]
     */
    protected $_id_fds;

    /**
     * @var array[][]
     */
    protected $_name_fds;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['pubSub'])) {
            $this->_injections['pubSub'] = $options['pubSub'];
        }

        if (isset($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }

        if (isset($options['dedicated'])) {
            $this->_dedicated = (bool)$options['dedicated'];
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

        if (!$this->_dedicated) {
            $this->_fds[$fd] = true;
        }

        if (($id = $this->identity->getId('')) !== '') {
            $this->_id_fds[$room][$id][$fd] = true;
        }

        if (($name = $this->identity->getName('')) !== '') {
            $this->_name_fds[$room][$name][$fd] = true;
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

        if (!$this->_dedicated) {
            unset($this->_fds[$fd]);
        }

        if (($id = $this->identity->getId('')) !== '') {
            unset($this->_id_fds[$room][$id][$fd]);
            if (count($this->_id_fds[$room][$id]) === 0) {
                unset($this->_id_fds[$room][$id]);
            }
        }

        if (($name = $this->identity->getName('')) !== '') {
            unset($this->_name_fds[$room][$name][$fd]);
            if (count($this->_name_fds[$room][$name]) === 0) {
                unset($this->_name_fds[$room][$name]);
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
        $sent_fds = [];

        foreach ($this->_id_fds[$room] ?? [] as $fds) {
            foreach ($fds as $fd => $_) {
                $sent_fds[$fd] = true;
                $this->push($fd, $message);
            }
        }

        foreach ($this->_name_fds[$room] ?? [] as $fds) {
            foreach ($fds as $fd => $_) {
                if (!isset($sent_fds[$fd])) {
                    $sent_fds[$fd] = true;
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
            foreach ($this->_id_fds[$room][$id] ?? [] as $fd => $_) {
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
            foreach ($this->_name_fds[$room][$name] ?? [] as $fd => $_) {
                $this->push($fd, $message);
            }
        }
    }

    /**
     * @param string $message
     */
    public function broadcast($message)
    {
        if ($this->_dedicated) {
            $this->wsServer->broadcast($message);
        } else {
            foreach ($this->_fds as $fd => $_) {
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
        $sent_fds = [];
        foreach ($this->_id_fds[$room] ?? [] as $fds) {
            foreach ($fds as $fd => $_) {
                $sent_fds[$fd] = true;
                $this->push($fd, $message);
            }
        }
        unset($this->_id_fds[$room]);

        foreach ($this->_name_fds[$room] ?? [] as $fds) {
            foreach ($fds as $fd => $_) {
                if (!isset($sent_fds[$fd])) {
                    $sent_fds[$fd] = true;
                    $this->push($fd, $message);
                }
            }
        }
        unset($this->_name_fds[$room]);
    }

    /**
     * @param string $room
     * @param array  $receivers
     * @param string $message
     */
    public function kickoutId($room, $receivers, $message)
    {
        $sent_fds = [];

        foreach ($receivers as $id) {
            foreach ($this->_id_fds[$room][$id] ?? [] as $fds) {
                foreach ($fds as $fd => $_) {
                    $sent_fds[$fd] = true;
                    $this->push($fd, $message);
                }
            }
            unset($this->_id_fds[$room][$id]);
        }

        foreach ($this->_name_fds[$room] ?? [] as $name => $fds) {
            foreach ($fds as $fd => $_) {
                if (isset($sent_fds[$fd])) {
                    unset($this->_name_fds[$room][$name]);
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
        $sent_fds = [];

        foreach ($receivers as $name) {
            foreach ($this->_name_fds[$room][$name] ?? [] as $fds) {
                foreach ($fds as $fd => $_) {
                    $sent_fds[$fd] = true;
                    $this->push($fd, $message);
                }
            }
            unset($this->_name_fds[$room][$name]);
        }

        foreach ($this->_id_fds[$room] ?? [] as $id => $fds) {
            foreach ($fds as $fd => $_) {
                if (isset($sent_fds[$fd])) {
                    unset($this->_id_fds[$room][$id]);
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
                    [$this->_prefix . '*'], function ($channel, $message) {
                    list($type, $room, $receivers) = explode(':', substr($channel, strlen($this->_prefix)), 4);
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