<?php
namespace App;

class Router extends \ManaPHP\Router
{
    public function __construct()
    {
        parent::__construct(true);

        $this->_areas = ['Menu', 'Rbac', 'User'];
    }
}