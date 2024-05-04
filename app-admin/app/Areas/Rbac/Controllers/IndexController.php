<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Controllers;

use App\Controllers\Controller;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\GetMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;

#[RequestMapping('')]
class IndexController extends Controller
{
    #[Authorize('user')]
    #[GetMapping('/rbac')]
    public function indexAction()
    {
        return $this->response->redirect('permission/');
    }
}