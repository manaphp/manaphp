<?php

namespace App\Commands;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Ws\Pushing\ClientInterface;

class PushCommand extends Command
{
    #[Autowired] protected ClientInterface $wspClient;

    /**
     * @param string $name
     * @param string $message
     * @param string $endpoint
     */
    public function nameAction(string $name = 'admin', string $message = 'name_msg', $endpoint = null)
    {
        $this->wspClient->pushToName($name, $message, $endpoint);
    }

    /**
     * @param string $room
     * @param string $message
     * @param string $endpoint
     */
    public function roomAction(string $room = 'meeting', string $message = 'room_msg', $endpoint = null)
    {
        $this->wspClient->pushToRoom($room, $message, $endpoint);
    }

    /**
     * @param string $role
     * @param string $message
     * @param string $endpoint
     */
    public function roleAction(string $role = 'admin', string $message = 'role_msg', $endpoint = null)
    {
        $this->wspClient->pushToRole($role, $message, $endpoint);
    }

    /**
     * @param string $id
     * @param string $message
     * @param string $endpoint
     */
    public function idAction(string $id = '1', string $message = 'id_msg', $endpoint = null)
    {
        $this->wspClient->pushToId($id, $message, $endpoint);
    }

    public function allAction($message = 'all_msg', $endpoint = null)
    {
        $this->wspClient->pushToAll($message, $endpoint);
    }

    /**
     * @param string $message
     * @param string $endpoint
     */
    public function broadcastAction(string $message = 'broadcast_msg', $endpoint = null)
    {
        $this->wspClient->broadcast($message, $endpoint);
    }
}