<?php
declare(strict_types=1);

namespace App\Areas\Bos\Controllers;

use App\Controllers\Controller;
use ManaPHP\Bos\ClientInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\PostMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mvc\View\Attribute\ViewGetMapping;

#[Authorize]
#[RequestMapping('/bos/bucket')]
class BucketController extends Controller
{
    #[Autowired] protected ClientInterface $bosClient;

    #[ViewGetMapping('')]
    public function indexAction()
    {
        return $this->bosClient->listBuckets();
    }

    #[PostMapping]
    public function createAction($bucket_name, $base_url)
    {
        return $this->bosClient->createBucket($bucket_name, $base_url);
    }
}