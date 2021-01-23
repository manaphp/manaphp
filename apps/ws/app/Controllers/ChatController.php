<?php

namespace App\Controllers;

/**
 * @property-read \ManaPHP\Ws\Chatting\ServerInterface $chatServer
 */
class ChatController extends Controller
{
    public function startAction()
    {
        $this->chatServer->start();
    }

    public function openAction($fd)
    {
        $this->chatServer->open($fd, $this->request->get('room_id', 'meeting'));
    }

    public function closeAction($fd)
    {
        $this->chatServer->open($fd, $this->request->get('room_id', 'meeting'));
    }
}