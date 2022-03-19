<?php

namespace App\Areas\Bos\Controllers;

use App\Controllers\Controller;
use ManaPHP\Http\Controller\Attribute\Authorize;

/**
 * @property-read \ManaPHP\Bos\ClientInterface $bosClient
 */
#[Authorize('@index')]
class BucketController extends Controller
{
    public function indexAction()
    {
        return $this->bosClient->listBuckets();
    }

    public function createAction($bucket_name, $base_url)
    {
        return $this->bosClient->createBucket($bucket_name, $base_url);
    }
}