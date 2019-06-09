<?php

namespace App\Controllers;

use ManaPHP\Mvc\Controller;

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
