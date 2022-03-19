<?php
declare(strict_types=1);

namespace App;

use App\Controllers\CustomerController;
use App\Controllers\TimeController;

class Router extends \ManaPHP\Http\Router
{
    public function __construct()
    {
        parent::__construct(true);
        $this->add('/', [TimeController::class, 'current']);
        $this->add('/time/current', [TimeController::class, 'current']);
        $this->add('/time/timestamp', [TimeController::class, 'timestamp']);
        $this->addRest('/customers', CustomerController::class);
    }
}