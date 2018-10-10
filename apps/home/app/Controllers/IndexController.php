<?php
namespace App\Controllers;

use ManaPHP\Version;

class IndexController extends ControllerBase
{
    public function indexAction()
    {
        return $this->response->redirect('about');
    }

    /**
     * @throws \ManaPHP\Curl\ConnectionException
     */
    public function aboutAction()
    {
        $this->view->setVar('version', Version::get());
        $this->view->setVar('current_time', date('Y-m-d H:i:s'));

        $this->view->setVar('baidu_time', date('Y-m-d H:i:s', strtotime($this->httpClient->get('https://www.baidu.com/')->getHeaders()['Date'])));

        $this->flash->error(date('Y-m-d H:i:s'));
    }
}
