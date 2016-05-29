<?php
namespace Application\Home\Controllers;

use Application\Api\Controllers\ControllerBase;
use Application\Home\Models\City;
use ManaPHP\Configure;
use ManaPHP\Version;

class IndexController extends ControllerBase
{
    public function indexAction()
    {
        //$city = City::findFirst(1);
        $this->dispatcher->forward('about');
    }

    public function aboutAction()
    {
        $this->view->setVar('version', Version::get());
        $this->view->setVar('current_time', date('Y-m-d H:i:s'));
    }
}
