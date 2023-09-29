<?php
declare(strict_types=1);

namespace App\Areas\Admin\Controllers;

use App\Controllers\Controller;
use App\Models\AdminLoginLog;
use ManaPHP\Http\Controller\Attribute\AcceptVerbs;
use ManaPHP\Http\Controller\Attribute\Authorize;

class LoginLogController extends Controller
{
    #[Authorize]
    public function indexAction()
    {
        return AdminLoginLog::select(
            ['login_id', 'admin_id', 'admin_name', 'client_udid', 'user_agent', 'client_ip', 'created_time']
        )
            ->orderBy(['login_id' => SORT_DESC])
            ->whereCriteria($this->request->all(), ['admin_id', 'admin_name*=', 'client_ip', 'client_udid', 'created_time@='])
            ->paginate();
    }

    #[AcceptVerbs(['GET'])]
    #[Authorize('user')]
    public function latestAction()
    {
        return AdminLoginLog::select(['login_id', 'client_udid', 'user_agent', 'client_ip', 'created_time'])
            ->whereCriteria($this->request->all(), ['created_time@='])
            ->orderBy(['login_id' => SORT_DESC])
            ->where(['admin_id' => $this->identity->getId()])
            ->paginate();
    }
}