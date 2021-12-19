<?php
declare(strict_types=1);

namespace App\Controllers;

use ManaPHP\Version;

class IndexController extends Controller
{
    public function getAcl(): array
    {
        return ['*' => '*'];
    }

    public function indexAction()
    {
        return $this->response->redirect('about');
    }

    public function aboutView()
    {
        $this->view->setVar('version', Version::get());
        $this->view->setVar('current_time', date('Y-m-d H:i:s'));

        $this->flash->error(date('Y-m-d H:i:s'));
    }

    public function aboutAction()
    {

    }
}
