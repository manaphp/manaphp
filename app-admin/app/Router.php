<?php
declare(strict_types=1);

namespace App;

class Router extends \ManaPHP\Http\Router
{
    public function __construct()
    {
//        $areas = 'admin|bos|menu|rbac|system';
//
//        $this->add('/{controller}', 'App\Controllers\{controller}Controller::indexAction');
//        $this->add('/{controller}/{action}', 'App\Controllers\{controller}Controller::{action}Action');
//        $this->add("/{area:$areas}/{controller}", 'App\Areas\{area}\Controllers\{controller}Controller::indexAction');
//        $this->add(
//            "/{area:$areas}/{controller}/{action}",
//            'App\Areas\{area}\Controllers\{controller}Controller::{action}Action'
//        );

//        $this->add('/', [IndexController::class, 'indexAction']);
//        $this->add('/login', [SessionController::class, 'loginAction']);
//        $this->addGet('/logout', [SessionController::class, 'logoutAction']);
    }
}