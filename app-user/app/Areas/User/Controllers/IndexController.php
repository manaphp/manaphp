<?php
declare(strict_types=1);

namespace App\Areas\User\Controllers;

use App\Controllers\Controller;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\GetMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mvc\View\Attribute\ViewGetMapping;

#[Authorize(Authorize::USER)]
#[RequestMapping('/user')]
class IndexController extends Controller
{
    #[ViewGetMapping('')]
    public function indexAction()
    {

    }

    #[GetMapping]
    public function timeAction()
    {
        return ['timestamp' => round(microtime(true), 3), 'human' => date('Y-m-d H:i:s')];
    }
}