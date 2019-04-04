<?php
namespace App\Areas\User\Controllers;

use App\Models\AdminLoginLog;
use ManaPHP\Mvc\Controller;

class LoginLogController extends Controller
{
    public function indexAction()
    {
        return $this->request->isAjax()
            ? AdminLoginLog::query()
                ->select(['login_id', 'admin_id', 'admin_name', 'client_udid', 'user_agent', 'client_ip', 'created_time'])
                ->orderBy('login_id DESC')
                ->whereSearch(['admin_id', 'admin_name*=', 'client_ip', 'client_udid', 'created_time@='])
                ->paginate()
            : null;
    }
}