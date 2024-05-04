<?php
declare(strict_types=1);

namespace App\Areas\Admin\Controllers;

use App\Controllers\Controller;
use App\Models\AdminLoginLog;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\GetMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;

#[RequestMapping('/admin/login-log')]
class LoginLogController extends Controller
{
    #[Authorize]
    #[GetMapping('')]
    public function indexAction(int $page = 1, int $size = 10)
    {
        return AdminLoginLog::select(
            ['login_id', 'admin_id', 'admin_name', 'client_udid', 'user_agent', 'client_ip', 'created_time']
        )
            ->orderBy(['login_id' => SORT_DESC])
            ->whereCriteria(
                $this->request->all(), ['admin_id', 'admin_name*=', 'client_ip', 'client_udid', 'created_time@=']
            )
            ->paginate($page, $size);
    }

    #[Authorize('user')]
    #[GetMapping]
    public function latestAction(int $page = 1, int $size = 10)
    {
        return AdminLoginLog::select(['login_id', 'client_udid', 'user_agent', 'client_ip', 'created_time'])
            ->whereCriteria($this->request->all(), ['created_time@='])
            ->orderBy(['login_id' => SORT_DESC])
            ->where(['admin_id' => $this->identity->getId()])
            ->paginate($page, $size);
    }
}