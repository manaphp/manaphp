<?php

namespace App;

use App\Controllers\TimeController;

class Router extends \ManaPHP\Router
{
    public function __construct()
    {
        parent::__construct();

        $this->add('/', [TimeController::class, 'current']);
    }
}