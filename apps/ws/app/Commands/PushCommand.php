<?php

namespace App\Commands;

class PushCommand extends Command
{
    /**
     * @param string $name
     * @param string $message
     * @param string $endpoint
     */
    public function nameAction($name = 'admin', $message = 'name_msg', $endpoint = null)
    {
        $this->wspClient->pushToName($name, $message, $endpoint);
    }

    /**
     * @param string $role
     * @param string $message
     * @param string $endpoint
     */
    public function roleAction($role = 'admin', $message = 'role_msg', $endpoint = null)
    {
        $this->wspClient->pushToRole($role, $message, $endpoint);
    }

    /**
     * @param string $id
     * @param string $message
     * @param string $endpoint
     */
    public function idAction($id = '1', $message = 'id_msg', $endpoint = null)
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
    public function broadcastAction($message = 'broadcast_msg', $endpoint = null)
    {
        $this->wspClient->broadcast($message, $endpoint);
    }
}