<?php
namespace App\Admin\Rbac\Controllers;

use ManaPHP\Mvc\Controller;

class IndexController extends Controller
{
    public function indexAction()
    {
        return $this->response->redirectToAction('permission/');
    }
}