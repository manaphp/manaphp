<?php
declare(strict_types=1);

namespace App\Areas\Bos\Controllers;

use App\Controllers\Controller;
use ManaPHP\Bos\ClientInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Controller\Attribute\Authorize;
use Throwable;

#[Authorize('@index')]
class ObjectController extends Controller
{
    #[Autowired] protected ClientInterface $bosClient;

    public function bucketsAction()
    {
        return $this->bosClient->listBuckets();
    }

    public function indexAction($bucket_name = '', string $prefix = '', string $extension = '', int $page = 1,
        int $size = 10
    ) {
        $filters = [];

        $filters['prefix'] = $prefix;
        $filters['extension'] = $extension;
        $filters['page'] = $page;
        $filters['size'] = $size;

        try {
            return $this->bosClient->listObjects($bucket_name, $filters);
        } catch (Throwable $throwable) {
            return $throwable->getMessage();
        }
    }

    public function getUploadTokenAction($bucket_name, $key, $insert_only)
    {
        if ($key === '') {
            return 'key不能为空';
        }

        $url = $this->bosClient->getPutObjectUrl($bucket_name, $key, ['insert_only' => $insert_only !== 'false']);
        return $this->response->json(['code' => 0, 'msg' => '', 'data' => $url]);
    }
}