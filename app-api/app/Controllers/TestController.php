<?php
declare(strict_types=1);

namespace App\Controllers;

use ManaPHP\Http\Router\Attribute\GetMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;

#[RequestMapping('/test')]
class TestController extends Controller
{
    #[GetMapping]
    public function indexAction()
    {
        return 0;
    }
}