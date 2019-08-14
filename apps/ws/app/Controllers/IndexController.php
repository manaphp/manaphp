<?php

namespace App\Controllers;

use ManaPHP\WebSocket\Controller;

class IndexController extends Controller
{
    public function openAction()
    {
        $token = $this->request->getToken();
        $this->identity->setClaims(jwt_decode($token, 'pusher.admin'));
    }
}
