<?php
namespace Application\Admin;

class Module extends \ManaPHP\Mvc\Module
{
    public function registerServices($di)
    {

    }

    public function authorize($controller, $action)
    {
//      return $this->response->redirect('http://www.baidu.com/');

//      $this->dispatcher->forward('index/about');

        return true;
    }
}