<?php
declare(strict_types=1);

namespace App\Controllers;

class IndexController extends Controller
{
    public function getAcl(): array
    {
        return ['*' => 'user'];
    }

    public function indexAction()
    {

    }
}