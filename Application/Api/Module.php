<?php
namespace Application\Api;

use ManaPHP\Component;
use ManaPHP\Mvc\ModuleInterface;

class Module extends Component implements ModuleInterface
{
    public function registerServices($di)
    {

    }

    public function authorize($controller, $action)
    {
        return true;
    }
}