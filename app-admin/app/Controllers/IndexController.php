<?php

namespace App\Controllers;

class IndexController extends Controller
{
    public function getAcl()
    {
        return ['*' => 'user'];
    }

    public function indexAction()
    {

    }

    public function timeAction()
    {
        return ['timestamp' => round(microtime(true), 3), 'human' => date('Y-m-d H:i:s')];
    }
}