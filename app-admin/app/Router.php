<?php
declare(strict_types=1);

namespace App;

use App\Areas\Admin\Controllers\SessionController;

class Router extends \ManaPHP\Http\Router
{
    public function __construct()
    {
        parent::__construct(true);

        $this->setAreas();

        $this->addGet('/login', [SessionController::class, 'login']);
        $this->addGet('/logout', [SessionController::class, 'logout']);
    }
}