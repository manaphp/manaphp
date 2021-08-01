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
}