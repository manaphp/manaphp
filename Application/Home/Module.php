<?php
namespace Application\Home;

class Module extends \ManaPHP\Mvc\Module
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