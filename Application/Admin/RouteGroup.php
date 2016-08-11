<?php
namespace Application\Admin;

use Application\Home\Controllers\IndexController;
use ManaPHP\Mvc\Router\Group;

class RouteGroup extends Group
{
    public function __construct()
    {
        parent::__construct(true);
    }
}