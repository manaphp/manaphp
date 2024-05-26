<?php
declare(strict_types=1);

namespace App\Areas\Admin\Controllers;

use App\Controllers\Controller;
use App\Repositories\AdminLoginLogRepository;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mvc\View\Attribute\ViewGetMapping;
use ManaPHP\Persistence\Page;
use ManaPHP\Persistence\Restrictions;

#[RequestMapping('/admin/login-log')]
class LoginLogController extends Controller
{
    #[Autowired] protected AdminLoginLogRepository $adminLoginLogRepository;

    #[Authorize]
    #[ViewGetMapping('')]
    public function indexAction(int $page = 1, int $size = 10)
    {
        $fields = ['login_id', 'admin_id', 'admin_name', 'client_udid', 'user_agent', 'client_ip', 'created_time'];
        $restrictions = Restrictions::of(
            $this->request->all(),
            ['admin_id', 'admin_name*=', 'client_ip', 'client_udid', 'created_time@=']
        );
        $orders = ['login_id' => SORT_DESC];
        return $this->adminLoginLogRepository->paginate($restrictions, $fields, $orders, Page::of($page, $size));
    }

    #[Authorize(Authorize::USER)]
    #[ViewGetMapping]
    public function latestAction(int $page = 1, int $size = 10)
    {
        $fields = ['login_id', 'client_udid', 'user_agent', 'client_ip', 'created_time'];

        $restrictions = Restrictions::of($this->request->all(), ['created_time@=']);
        $restrictions->eq('admin_id', $this->identity->getId());

        $orders = ['login_id' => SORT_DESC];

        return $this->adminLoginLogRepository->paginate($restrictions, $fields, $orders, Page::of($page, $size));
    }
}