<?php
declare(strict_types=1);

namespace App\Controllers;

use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mvc\View\Attribute\ViewGetMapping;

#[Authorize('user')]
#[RequestMapping('')]
class IndexController extends Controller
{
    #[ViewGetMapping('/')]
    public function indexAction()
    {

    }
}