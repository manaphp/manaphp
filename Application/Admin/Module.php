<?php
namespace Application\Admin;

use ManaPHP\Component;
use ManaPHP\Mvc\ModuleInterface;

class Module extends Component implements ModuleInterface
{
    public function registerServices($di)
    {

    }

    public function authorize($controller, $action)
    {
//      $this->response->redirect('http://www.baidu.com/');
//      return false;

//      $this->dispatcher->forward('index/about');

        return true;
    }
}