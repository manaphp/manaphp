<?php
declare(strict_types=1);

namespace App;

use App\Controllers\BenchmarkController;
use App\Controllers\IndexController;

class Router extends \ManaPHP\Http\Router
{
    public function __construct()
    {
        parent::__construct();
        $this->setAreas();

        $this->addGet('/user/login', '/user/session/login');
        $this->addGet('/admin/login', '/admin/session/login');
        $this->addGet('/about', 'Index::about');
        $this->addGet('/about1', 'Index::about');
        $this->addGet('/about2', [IndexController::class, 'about']);
        $this->addGet('/about3', ['controller' => IndexController::class, 'action' => 'about']);
        $this->addGet('/bench', BenchmarkController::class);
    }
}