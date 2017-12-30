<?php

namespace App\Api\Controllers;

class TimeController extends ControllerBase
{
    public function currentAction()
    {
        $data = [];
        $data['current_time'] = date('Y-m-d H:i:s');
        $data['memory_usage'] = round(memory_get_usage(false) / 1024) . 'KB';
        $data['process_time'] = sprintf('%.03f', microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);
        return $this->response->setJsonContent(['code' => 0, 'message' => '', 'data' => $data]);
    }

    public function timestampAction()
    {
        $access_token = $this->request->getAccessToken();
        if ($access_token !== 'manaphp') {
            return $this->response->setJsonContent(['code' => __LINE__, 'message' => $access_token === '' ? 'access_token is missing.' : 'access_token is wrong.']);
        }

        $time = time();
        $data = [];
        $data['timestamp'] = $time;
        $data['time_human'] = date('Y-m-d H:i:s', $time);

        return $this->response->setJsonContent(['code' => 0, 'message' => '', 'data' => $data]);
    }
}
