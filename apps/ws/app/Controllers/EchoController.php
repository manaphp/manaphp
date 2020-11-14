<?php

namespace App\Controllers;

use ManaPHP\WebSocket\Controller;

class EchoController extends Controller
{
    public function messageAction($fd, $data)
    {
        //    $this->wsServer->push($fd, $data);
        return $this->response->setContent($data);
    }
}