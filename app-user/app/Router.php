<?php
declare(strict_types=1);

namespace App;

use App\Areas\Admin\Controllers\SessionController as AdminSessionController;
use App\Areas\User\Controllers\SessionController as UserSessionController;
use App\Controllers\BenchmarkController;
use App\Controllers\IndexController;

class Router extends \ManaPHP\Http\Router
{
    public function __construct()
    {
        $areas = 'user';
        $this->add('/{controller}', 'App\Controllers\{controller}Controller::indexAction');
        $this->add('/{controller}/{action}', 'App\Controllers\{controller}Controller::{action}Action');
        $this->add("/{area:$areas}/{controller}", 'App\Areas\{area}\Controllers\{controller}Controller::indexAction');
        $this->add("/{area:$areas}/{controller}/{action}", 'App\Areas\{area}\Controllers\{controller}Controller::{action}Action');
        $this->addGet('/user/login', [UserSessionController::class, 'loginAction']);
        $this->addGet('/admin/login', [AdminSessionController::class, 'logoutAction']);
        $this->addGet('/about', 'Index::about');
        $this->addGet('/about1', 'Index::about');
        $this->addGet('/about2', [IndexController::class, 'aboutAction']);
        $this->addGet('/bench', [BenchmarkController::class, 'indexAction']);
    }
}