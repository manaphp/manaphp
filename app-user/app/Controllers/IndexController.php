<?php
declare(strict_types=1);

namespace App\Controllers;

use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\GetMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mvc\View\Attribute\ViewGetMapping;
use ManaPHP\Version;

#[Authorize(Authorize::GUEST)]
#[RequestMapping('')]
class IndexController extends Controller
{
    #[GetMapping('/')]
    public function indexAction()
    {
        return $this->response->redirect('about');
    }

    public function aboutVars(): array
    {
        $vars = [];

        $vars['version'] = Version::get();
        $vars['current_time'] = date('Y-m-d H:i:s');

        $this->flash->error(date('Y-m-d H:i:s'));

        return $vars;
    }

    #[ViewGetMapping(vars: 'aboutVars')]
    public function aboutAction()
    {

    }
}
