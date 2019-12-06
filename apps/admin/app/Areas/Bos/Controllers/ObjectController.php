<?php
namespace App\Areas\Bos\Controllers;

use ManaPHP\Mvc\Controller;

class ObjectController extends Controller
{
    public function bucketsAction()
    {
        return $this->bosClient->listBuckets();
    }

    public function indexAction($bucket_name = '')
    {
        $filters = [];

        $filters['prefix'] = input('prefix', '');
        $filters['extension'] = input('extension', '');
        $filters['page'] = input('page', 1);
        $filters['size'] = input('size', 10);

        return $this->bosClient->listObjects($bucket_name, $filters);
    }

    public function getUploadTokenAction($bucket_name, $key, $insert_only)
    {
        if ($key === '') {
            return 'key不能为空';
        }

        $url = $this->bosClient->getPutObjectUrl($bucket_name, $key, ['insert_only' => $insert_only !== 'false']);
        return $this->response->setJsonData($url);
    }
}