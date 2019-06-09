<?php

namespace App\Controllers;

use ManaPHP\WebSocket\Controller;

class IndexController extends Controller
{
    public function getAcl()
    {
        return ['*' => '*'];
    }

    public function indexAction()
    {
        return $this->request->getServer();
    }
}
