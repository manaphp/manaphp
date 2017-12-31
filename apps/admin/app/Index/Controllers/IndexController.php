<?php
namespace App\Admin\Index\Controllers;

use ManaPHP\Mvc\Controller;

class IndexController extends Controller
{
    public function indexAction()
    {
        return $this->response->redirectToAction('/rbac');
    }
}