<?php

namespace ManaPHP\Ws\Chatting;

use ManaPHP\Component;

/**
 * @property-read \ManaPHP\Messaging\PubSubInterface $pubSub
 */
class Client extends Component implements ClientInterface
{
    /**
     * @var string
     */
    protected $prefix = 'ws_chatting:';

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['prefix'])) {
            $this->prefix = $options['prefix'];
        }
    }

    /**
     * @param string       $type
     * @param string       $room
     * @param string|array $receivers
     * @param string|array $message
     */
    protected function push($type, $room, $receivers, $message)
    {
        if (!is_string($message)) {
            $message = json_stringify($message);
        }

        if (is_array($receivers)) {
            $receivers = implode(',', $receivers);
        }

        $this->fireEvent('chatClient:push', compact('type', 'room', 'receivers', 'message'));

        $this->pubSub->publish($this->prefix . "$type:$room:" . $receivers, $message);
    }

    /**
     * @param string       $room
     * @param string|array $message
     *
     * @return void
     */
    public function pushToRoom($room, $message)
    {
        $this->push('message.room', $room, '*', $message);
    }

    /**
     * @param string       $room
     * @param array|string $id
     * @param array|string $message
     *
     * @return mixed|void
     */
    public function pushToId($room, $id, $message)
    {
        $this->push("message.id", $room, $id, $message);
    }

    /**
     * @param string       $room
     * @param array|string $name
     * @param array|string $message
     *
     * @return mixed|void
     */
    public function pushToName($room, $name, $message)
    {
        $this->push("message.name", $room, $name, $message);
    }

    public function broadcast($message)
    {
        $this->push('message.broadcast', '*', '*', $message);
    }

    /**
     * @param string|array $room
     * @param string|array $message
     *
     * @return void
     */
    public function closeRoom($room, $message)
    {
        $this->push('room.close', $room, '*', $message);
    }

    /**
     * @param string       $room
     * @param string|array $id
     * @param string|array $message
     *
     * @return void
     */
    public function kickoutId($room, $id, $message)
    {
        $this->push("kickout.id", $room, $id, $message);
    }

    /**
     * @param string       $room
     * @param string|array $name
     * @param string|array $message
     *
     * @return void
     */
    public function kickoutName($room, $name, $message)
    {
        $this->push('kickout.name', $room, $name, $message);
    }
}