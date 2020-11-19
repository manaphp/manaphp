<?php

namespace App\Controllers;

class EchoController extends Controller
{
    public function messageAction($fd, $data)
    {
        //    $this->wsServer->push($fd, $data);
        return $this->response->setContent($data);
    }
}