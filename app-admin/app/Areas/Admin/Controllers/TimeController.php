<?php
declare(strict_types=1);

namespace App\Areas\Admin\Controllers;

use App\Controllers\Controller;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\GetMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;

#[Authorize(Authorize::GUEST)]
#[RequestMapping('/admin/time')]
class TimeController extends Controller
{
    #[GetMapping('')]
    public function indexAction()
    {
        return ['timestamp' => round(microtime(true), 3), 'human' => date('Y-m-d H:i:s')];
    }
}