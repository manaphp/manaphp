<?php

namespace App\Areas\User\Controllers;

use App\Controllers\Controller;
use ManaPHP\Http\Controller\Attribute\Authorize;

#[Authorize('user')]
class IndexController extends Controller
{
    public function indexAction()
    {

    }

    public function timeAction()
    {
        return ['timestamp' => round(microtime(true), 3), 'human' => date('Y-m-d H:i:s')];
    }
}