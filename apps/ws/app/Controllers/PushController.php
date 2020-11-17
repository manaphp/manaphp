<?php

namespace App\Controllers;

use ManaPHP\Ws\Controller;

class PushController extends Controller
{
    public function startAction()
    {
        $this->wspServer->start();
    }
}