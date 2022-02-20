<?php

namespace App\Controllers;

use ManaPHP\Http\Controller\Attribute\Authorize;

#[Authorize('*')]
class TestController extends Controller
{
    public function indexAction()
    {
        return 0;
    }
}