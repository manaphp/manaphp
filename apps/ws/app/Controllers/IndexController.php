<?php

namespace App\Controllers;

use ManaPHP\Mvc\Controller;
use ManaPHP\Version;

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
