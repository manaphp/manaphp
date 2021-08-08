<?php

namespace App\Areas\User\Controllers;

use App\Controllers\Controller;
use App\Models\UserLoginLog;

class LoginLogController extends Controller
{
    public function getAcl()
    {
        return ['*' => '@index', 'latest' => 'user'];
    }

    public function getVerbs()
    {
        return array_merge(
            parent::getVerbs(), [
                'latest' => 'GET'
            ]
        );
    }

    public function latestAction()
    {
        return UserLoginLog::select(['login_id', 'client_udid', 'user_agent', 'client_ip', 'created_time'])
            ->search(['created_time@='])
            ->orderBy(['login_id' => SORT_DESC])
            ->where(['user_id' => $this->identity->getId()])
            ->paginate();
    }
}