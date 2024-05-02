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
        parent::__construct();
        $this->setAreas();

        $this->addGet('/user/login', [UserSessionController::class, 'loginAction']);
        $this->addGet('/admin/login', [AdminSessionController::class, 'logoutAction']);
        $this->addGet('/about', 'Index::about');
        $this->addGet('/about1', 'Index::about');
        $this->addGet('/about2', [IndexController::class, 'aboutAction']);
        $this->addGet('/bench', BenchmarkController::class);
    }
}