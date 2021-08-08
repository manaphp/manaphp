<?php

namespace App;

use App\Controllers\BenchmarkController;
use App\Controllers\IndexController;

class Router extends \ManaPHP\Http\Router
{
    public function __construct()
    {
        parent::__construct();
        $this->setAreas();

        $this->add('/user/login', '/user/session/login');
        $this->add('/admin/login', '/admin/session/login');
        $this->add('/about', 'Index::about');
        $this->addGet('/about1', 'Index::about');
        $this->addGet('/about2', [IndexController::class, 'about']);
        $this->addGet('/about3', ['controller' => IndexController::class, 'action' => 'about']);
        $this->add('/bench', BenchmarkController::class);
    }
}