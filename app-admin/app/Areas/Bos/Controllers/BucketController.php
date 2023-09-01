<?php
declare(strict_types=1);

namespace App\Areas\Bos\Controllers;

use App\Controllers\Controller;
use ManaPHP\Bos\ClientInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Http\Controller\Attribute\Authorize;

#[Authorize('@index')]
class BucketController extends Controller
{
    #[Inject] protected ClientInterface $bosClient;

    public function indexAction()
    {
        return $this->bosClient->listBuckets();
    }

    public function createAction($bucket_name, $base_url)
    {
        return $this->bosClient->createBucket($bucket_name, $base_url);
    }
}