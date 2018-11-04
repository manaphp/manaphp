<?php
namespace App\Controllers;

use ManaPHP\Mvc\Controller;

class IndexController extends Controller
{
    public function getAcl()
    {
        return ['index' => 'user'];
    }

    public function indexAction()
    {

    }
}