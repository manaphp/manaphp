<?php
declare(strict_types=1);

namespace App;

use App\Controllers\TimeController;

class Router extends \ManaPHP\Http\Router
{
    public function __construct()
    {
        $this->addGet('/', 'index::hello');
        $this->add('/{controller}', 'App\Controllers\{controller}Controller::indexAction');
        $this->add('/{controller}/{action}', 'App\Controllers\{controller}Controller::{action}Action');
        $this->addGet('/{controller}/{id}', 'App\Controllers\{controller}Controller::detailAction');
        $this->addGet('/time/current', [TimeController::class, 'currentAction']);
    }
}