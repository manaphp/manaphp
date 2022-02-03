<?php
declare(strict_types=1);

namespace ManaPHP\Ws\Chatting;

use ManaPHP\Component;

/**
 * @property-read \ManaPHP\Messaging\PubSubInterface $pubSub
 */
class Client extends Component implements ClientInterface
{
    protected string $prefix = 'ws_chatting:';

    public function __construct(array $options = [])
    {
        if (isset($options['prefix'])) {
            $this->prefix = $options['prefix'];
        }
    }

    protected function push(string $type, string $room, string|array $receivers, string|array $message): void
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

    public function pushToRoom(string $room, string|array $message): void
    {
        $this->push('message.room', $room, '*', $message);
    }

    public function pushToId(string $room, int|array $id, string|array $message): void
    {
        $this->push("message.id", $room, $id, $message);
    }

    public function pushToName(string $room, string|array $name, string|array $message): void
    {
        $this->push("message.name", $room, $name, $message);
    }

    public function broadcast(string|array $message): void
    {
        $this->push('message.broadcast', '*', '*', $message);
    }

    public function closeRoom(string $room, string|array $message): void
    {
        $this->push('room.close', $room, '*', $message);
    }

    public function kickoutId(string $room, string|array $id, string|array $message): void
    {
        $this->push("kickout.id", $room, $id, $message);
    }

    public function kickoutName(string $room, string|array $name, string|array $message): void
    {
        $this->push('kickout.name', $room, $name, $message);
    }
}