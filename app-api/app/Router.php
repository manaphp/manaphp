<?php
declare(strict_types=1);

namespace App;

use App\Controllers\TimeController;

class Router extends \ManaPHP\Http\Router
{
    public function __construct()
    {
        $this->prefix = '/api';

        parent::__construct(true);
        $this->add('/', 'index::hello');
        $this->addGet('/time/current', [TimeController::class, 'current']);
    }
}