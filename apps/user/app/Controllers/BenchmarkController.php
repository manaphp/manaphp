<?php

namespace App\Controllers;

use ManaPHP\Mvc\Controller;

class BenchmarkController extends Controller
{
    public function getAcl()
    {
        return ['*' => '*'];
    }

    public function indexAction()
    {

    }
}