<?php

namespace App\Areas\User\Controllers;

use App\Controllers\Controller;
use App\Models\UserLoginLog;
use ManaPHP\Http\Controller\Attribute\AcceptVerbs;

class LoginLogController extends Controller
{
    public function getAcl()
    {
        return ['*' => '@index', 'latest' => 'user'];
    }

    #[AcceptVerbs(['GET'])]
    public function latestAction()
    {
        return UserLoginLog::select(['login_id', 'client_udid', 'user_agent', 'client_ip', 'created_time'])
            ->search(['created_time@='])
            ->orderBy(['login_id' => SORT_DESC])
            ->where(['user_id' => $this->identity->getId()])
            ->paginate();
    }
}