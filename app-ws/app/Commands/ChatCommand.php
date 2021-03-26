<?php

namespace App\Commands;

/**
 * @property-read \ManaPHP\Ws\Chatting\ClientInterface $chatClient
 */
class ChatCommand extends Command
{
    public function roomAction($room = 'meeting', $message = 'room_msg')
    {
        $this->chatClient->pushToRoom($room, $message);
    }

    public function idAction($room = 'meeting', $id = '1', $message = 'id_msg')
    {
        $this->chatClient->pushToId($room, $id, $message);
    }

    public function nameAction($room = 'meeting', $name = 'admin', $message = 'name_msg')
    {
        $this->chatClient->pushToName($room, $name, $message);
    }

    public function broadcastAction($message = 'broadcast_msg')
    {
        $this->chatClient->broadcast($message);
    }

    public function closeAction($room = 'meeting', $message = 'close_msg')
    {
        $this->chatClient->closeRoom($room, $message);
    }

    public function kickoutIdAction($room = 'meeting', $id = '1', $message = 'kickout_id_msg')
    {
        $this->chatClient->kickoutId($room, $id, $message);
    }

    public function kickoutNameAction($room = 'meeting', $name = 'admin', $message = 'kickout_name_msg')
    {
        $this->chatClient->kickoutName($room, $name, $message);
    }
}