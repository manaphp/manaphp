<?php
declare(strict_types=1);

namespace App\Controllers;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Metrics\ExporterInterface;
use ManaPHP\Http\Router\Attribute\GetMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mvc\View\Attribute\ViewGetMapping;

#[Authorize(Authorize::USER)]
#[RequestMapping('')]
class IndexController extends Controller
{
    #[Autowired] protected ExporterInterface $exporter;

    #[ViewGetMapping('/')]
    public function indexAction()
    {

    }

    #[GetMapping]
    public function metricsAction()
    {
        return $this->exporter->export();
    }
}