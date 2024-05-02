<?php
declare(strict_types=1);

namespace App;

use App\Controllers\TimeController;

class Router extends \ManaPHP\Http\Router
{
    public function __construct()
    {
        parent::__construct(true);
        $this->addGet('/', 'index::hello');
        $this->addGet('/time/current', [TimeController::class, 'current']);
    }
}