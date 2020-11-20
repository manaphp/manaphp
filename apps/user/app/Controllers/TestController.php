<?php

namespace App\Controllers;

class TestController extends Controller
{
    public function getAcl()
    {
        return ['*' => '*'];
    }

    public function indexAction()
    {
        return 0;
    }
}