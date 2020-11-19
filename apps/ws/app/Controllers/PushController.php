<?php

namespace App\Controllers;

class PushController extends Controller
{
    public function startAction()
    {
        $this->wspServer->start();
    }

    public function openAction($fd)
    {
        $this->wspServer->open($fd);
    }

    public function closeAction($fd)
    {
        $this->wspServer->close($fd);
    }
}