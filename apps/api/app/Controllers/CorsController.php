<?php
namespace App\Controllers;

use ManaPHP\Rest\Controller;

class CorsController extends Controller
{
    public function getAcl()
    {
        return ['*' => '*'];
    }

    public function indexAction()
    {
        $this->response->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Credentials', 'true')
            ->setHeader('Access-Control-Allow-Headers', 'Origin, Accept, Authorization, Content-Type, X-Requested-With')
            ->setHeader('Access-Control-Allow-Methods', 'HEAD,GET,POST,PUT,DELETE');

        return $this->response->setContent('');
    }
}