<?php
declare(strict_types=1);

namespace App\Controllers;

use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\GetMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;

#[Authorize]
#[RequestMapping('/test')]
class TestController extends Controller
{
    #[GetMapping('')]
    public function indexAction()
    {

    }
}