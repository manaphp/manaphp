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
        return $this->response->setContent('');
    }
}