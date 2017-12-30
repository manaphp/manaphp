<?php
namespace App\Api;

use App\Api\Controllers\CustomerController;
use App\Api\Controllers\TimeController;

class Router extends \ManaPHP\Mvc\Router
{
    public function __construct()
    {
        parent::__construct(false);
        $this->addGet('/time/current', [TimeController::class, 'current']);
        $this->addGet('/time/timestamp', [TimeController::class, 'timestamp']);
        $this->addRest('/customers', CustomerController::class);
    }
}