<?php
declare(strict_types=1);

namespace App;

use App\Controllers\IndexController;
use App\Controllers\TimeController;

class Router extends \ManaPHP\Http\Router
{
    public function __construct()
    {
//        $this->addGet('/', [IndexController::class, 'helloAction']);
//        $this->addAny('/{controller}', 'App\Controllers\{controller}Controller::indexAction');
//        $this->addAny('/{controller}/{action}', 'App\Controllers\{controller}Controller::{action}Action');
//        $this->addGet('/{controller}/{id}', 'App\Controllers\{controller}Controller::detailAction');
//        $this->addGet('/time/current', [TimeController::class, 'currentAction']);
    }
}