<?php
declare(strict_types=1);

namespace App\Controllers;

use ManaPHP\Http\Controller\Attribute\Authorize;

#[Authorize('user')]
class IndexController extends Controller
{
    public function indexAction()
    {

    }
}