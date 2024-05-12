<?php
declare(strict_types=1);

namespace App\Areas\User\Controllers;

use App\Controllers\Controller;
use App\Repositories\UserLoginLogRepository;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mvc\View\Attribute\ViewGetMapping;
use ManaPHP\Persistence\Page;
use ManaPHP\Persistence\Restrictions;

#[RequestMapping('/user/login-log')]
class LoginLogController extends Controller
{
    #[Autowired] protected UserLoginLogRepository $userLoginLogRepository;

    #[Authorize('user')]
    #[ViewGetMapping]
    public function latestAction(int $page = 1, int $size = 10)
    {
        $fields = ['login_id', 'client_udid', 'user_agent', 'client_ip', 'created_time'];

        $restrictions = Restrictions::of($this->request->all(), ['created_time@=']);
        $restrictions->eq('user_id', $this->identity->getId());

        $orders = ['login_id' => SORT_DESC];

        return $this->userLoginLogRepository->paginate($fields, $restrictions, $orders, Page::of($page, $size));
    }
}