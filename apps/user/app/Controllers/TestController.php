<?php

namespace App\Controllers;

use ManaPHP\Mvc\Controller;

class TestController extends Controller
{
    public function getAcl()
    {
        return ['*' => '*'];
    }

    public function indexAction()
    {

    }
}