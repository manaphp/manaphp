<?php
declare(strict_types=1);

namespace App\Controllers;

class IndexController extends Controller
{
    public function helloAction()
    {
        return $this->response->setJsonData('Hello ManaPHP');
    }
}