<?php

namespace App\Controllers;

use ManaPHP\Rest\Controller;

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