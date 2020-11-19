<?php

namespace App\Controllers;

class TimeController extends Controller
{
    public function helloAction()
    {
        return $this->response->setContent('hello world!');
    }

    public function currentAction()
    {
        return time();
    }
}
