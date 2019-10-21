<?php

namespace App\Controllers;

use ManaPHP\Rest\Controller;

class TimeController extends Controller
{
    public function getAcl()
    {
        return ['*' => '*'];
    }

    public function helloAction()
    {
        return $this->response->setContent('hello world!');
    }

    public function currentAction()
    {
        return time();
    }
}
