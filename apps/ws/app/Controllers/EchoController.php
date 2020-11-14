<?php

namespace App\Controllers;

use ManaPHP\Ws\Controller;

class EchoController extends Controller
{
    public function messageAction($fd, $data)
    {
        //    $this->wsServer->push($fd, $data);
        return $this->response->setContent($data);
    }
}