<?php
namespace Application\Admin\Controllers;

use ManaPHP\Mvc\Controller;
use ManaPHP\Mvc\DispatcherInterface;

class ControllerBase extends Controller
{
    public function beforeExecuteRoute(DispatcherInterface $dispatcher)
    {
        $this->view->setLayout('Default');
    }
}