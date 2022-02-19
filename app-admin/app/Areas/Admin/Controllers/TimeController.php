<?php

namespace App\Areas\Admin\Controllers;

use App\Controllers\Controller;

class TimeController extends Controller
{
    public function getAcl(): array
    {
        return ['*' => '*'];
    }

    public function indexAction()
    {
        return ['timestamp' => round(microtime(true), 3), 'human' => date('Y-m-d H:i:s')];
    }
}