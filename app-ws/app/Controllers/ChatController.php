<?php

namespace App\Controllers;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Ws\Chatting\ServerInterface;

class ChatController extends Controller
{
    #[Autowired] protected ServerInterface $chatServer;

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