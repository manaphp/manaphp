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

        $this->add('/login', [SessionController::class, 'login']);
        $this->add('/logout', [SessionController::class, 'logout']);
    }
}