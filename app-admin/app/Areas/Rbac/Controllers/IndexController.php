<?php

namespace App\Areas\Rbac\Controllers;

use App\Controllers\Controller;
use ManaPHP\Http\Controller\Attribute\Authorize;

class IndexController extends Controller
{
    #[Authorize('user')]
    public function indexAction()
    {
        return $this->response->redirect('permission/');
    }
}