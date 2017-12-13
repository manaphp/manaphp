<?php
namespace Application\Api;

use Application\Api\Controllers\CustomerController;
use Application\Api\Controllers\TimeController;
use ManaPHP\Mvc\Router\Group;

class Router extends \ManaPHP\Mvc\Router
{
    public function __construct()
    {
        parent::__construct(false);
        $this->addGet('/time/current', [TimeController::class, 'current']);
        $this->addRest('/customers', CustomerController::class);
    }
}