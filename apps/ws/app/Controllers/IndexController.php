<?php

namespace App\Controllers;

use ManaPHP\WebSocket\Controller;

class IndexController extends Controller
{
    public function onOpen($fd)
    {
        $token = $this->request->getToken();
        $this->identity->setClaims(jwt_decode($token, 'pusher.admin'));
    }

    public function indexAction()
    {
        return $this->request->getServer();
    }
}
