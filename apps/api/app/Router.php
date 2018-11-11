<?php
namespace App;

use App\Controllers\CustomerController;
use App\Controllers\TimeController;

class Router extends \ManaPHP\Router
{
    public function __construct()
    {
        parent::__construct(false);
        $this->addGet('/', [TimeController::class, 'current']);
        $this->addGet('/time/current', [TimeController::class, 'current']);
        $this->addGet('/time/timestamp', [TimeController::class, 'timestamp']);
        $this->addRest('/customers', CustomerController::class);
        $this->addOptions();
    }
}