<?php

namespace App\Controllers;

use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Logging\LoggerInterface;
use Psr\Container\ContainerInterface;

#[Authorize('*')]
class TimeController extends Controller
{
    public function __construct(ContainerInterface $container)
    {
        $x=$container->get(LoggerInterface::class);
    }

    public function helloAction()
    {
        return $this->response->setContent('hello world!');
    }

    public function currentAction()
    {
        $data = [];
        $data['current_time'] = date('Y-m-d H:i:s');
//        $data['memory_usage'] = round(memory_get_usage(false) / 1024) . 'KB';
//        $data['process_time'] = sprintf('%.03f', microtime(true) - $this->request->getServer('REQUEST_TIME_FLOAT'));
//        $data['files'] = @get_included_files();
        return $data;
    }

    public function timestampAction()
    {
        $access_token = $this->request->getToken();
        if ($access_token !== 'manaphp') {
            return $access_token === '' ? 'access_token is missing.' : 'access_token is wrong.';
        }

        $time = time();
        $data = [];
        $data['timestamp'] = $time;
        $data['time_human'] = date('Y-m-d H:i:s', $time);

        return $data;
    }
}
