<?php

namespace App\Areas\User\Controllers;

use App\Controllers\Controller;
use App\Models\UserLoginLog;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\GetMapping;

class LoginLogController extends Controller
{
    #[GetMapping]
    #[Authorize('user')]
    public function latestAction(int $page = 1, int $size = 10)
    {
        return UserLoginLog::select(['login_id', 'client_udid', 'user_agent', 'client_ip', 'created_time'])
            ->whereCriteria($this->request->all(), ['created_time@='])
            ->orderBy(['login_id' => SORT_DESC])
            ->where(['user_id' => $this->identity->getId()])
            ->paginate($page, $size);
    }
}