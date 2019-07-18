<?php
namespace App;

use App\Areas\Admin\Controllers\SessionController;

class Router extends \ManaPHP\Router
{
    public function __construct()
    {
        parent::__construct(true);

        $this->_areas = ['Menu', 'Rbac', 'Admin'];
        $this->add('/login', [SessionController::class, 'login']);
        $this->add('/logout', [SessionController::class, 'logout']);
    }
}