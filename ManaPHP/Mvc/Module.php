<?php
namespace ManaPHP\Mvc;

use ManaPHP\Component;

/**
 * Class ManaPHP\Mvc\Module
 *
 * @package module
 */
class Module extends Component implements ModuleInterface
{
    public function registerServices($di)
    {

    }

    public function authorize($controller, $action)
    {
        //return $this->response->redirect('http://www.baidu.com/');

        //$this->dispatcher->forward('index/about');

        //$this->authorization->isAllowed($controller . '::' . $action);

        return true;
    }
}
