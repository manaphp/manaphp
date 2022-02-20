<?php

namespace App\Controllers;

use ManaPHP\Http\Controller\Attribute\Authorize;

#[Authorize('*')]
class BenchmarkController extends Controller
{
    public function indexAction()
    {

    }
}