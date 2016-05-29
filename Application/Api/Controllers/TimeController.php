<?php

namespace Application\Api\Controllers;

class TimeController extends ControllerBase
{
    public function currentAction()
    {
        $data = [];
        $data['current_time'] = date('Y-m-d H:i:s');
        return $this->response->setJsonContent(['code' => 0, 'error' => '', 'data' => $data]);
    }

    public function timestampAction()
    {
        if (!$this->request->hasQuery('access_token')) {
            return $this->response->setJsonContent(['code' => __LINE__, 'error' => 'access_token is missing.']);
        }
        $access_token = $this->request->getQuery('access_token');
        if ($access_token !== 'manaphp') {
            return $this->response->setJsonContent(['code' => __LINE__, 'error' => 'access_token is wrong.']);
        }

        $time = time();
        $data = [];
        $data['timestamp'] = $time;
        $data['time_human'] = date('Y-m-d H:i:s', $time);

        return $this->response->setJsonContent(['code' => 0, 'error' => '', 'data' => $data]);
    }
}
