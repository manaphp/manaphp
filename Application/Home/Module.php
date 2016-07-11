<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/11/21
 * Time: 22:21
 */
namespace Application\Home;

use ManaPHP\Component;
use ManaPHP\Mvc\ModuleInterface;

class Module extends Component implements ModuleInterface
{
    public function registerAutoloaders($di)
    {
    }

    public function registerServices($di)
    {

    }

    public function authorize($controller, $action)
    {
//      $this->response->redirect('http://www.baidu.com/');
//      return false;

//        $this->dispatcher->forward('index/about');
//        $this->response->redirect('http://www.baidu.com/');
//        return false;

        return true;
    }
}