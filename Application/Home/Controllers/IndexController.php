<?php
namespace Application\Home\Controllers;

use ManaPHP\Version;

class IndexController extends ControllerBase
{
//    public function onConstruct()
//    {
//        $this->_cacheOptions = [
//            'about' => 10,
//        ];
//    }

    public function indexAction()
    {

//        $city = City::findFirst(1, 200);

//        var_dump($city);
//        $this->modelsManager->createBuilder()
//            ->addFrom(City::class, 'c1')
//            ->leftJoin(City::class, 'c1.city_id =c2.city_id', 'c2')
//            ->executeEx($total, 10);
//        $this->cache->set('s/ss', 's/ss', 1);
        $this->dispatcher->forward('about');
    }

    public function aboutAction()
    {
        $this->view->setVar('version', Version::get());
        $this->view->setVar('current_time', date('Y-m-d H:i:s'));

        $this->flash->error(date('Y-m-d H:i:s'));
    }
}
