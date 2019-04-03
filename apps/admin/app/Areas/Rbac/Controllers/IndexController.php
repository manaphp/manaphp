<?php
namespace App\Areas\Rbac\Controllers;

use ManaPHP\Mvc\Controller;

class IndexController extends Controller
{
    public function indexAction()
    {
        return $this->response->redirect('permission/');
    }
}