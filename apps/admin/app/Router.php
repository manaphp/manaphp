<?php
namespace App\Admin;

class Router extends \ManaPHP\Mvc\Router
{
    public function __construct()
    {
        parent::__construct(false);
        $this->add('/:module/:controller/:action/:params');
    }
}