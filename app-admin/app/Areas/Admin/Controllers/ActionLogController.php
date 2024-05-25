<?php
declare(strict_types=1);

namespace App\Areas\Admin\Controllers;

use App\Controllers\Controller;
use App\Repositories\AdminActionLogRepository;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\GetMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mvc\View\Attribute\ViewGetMapping;
use ManaPHP\Persistence\Page;
use ManaPHP\Persistence\Restrictions;

#[RequestMapping('/admin/action-log')]
class ActionLogController extends Controller
{
    #[Autowired] protected AdminActionLogRepository $adminActionLogRepository;

    #[Authorize]
    #[ViewGetMapping('')]
    public function indexAction(int $page = 1, int $size = 10)
    {
        $restrictions = Restrictions::of(
            $this->request->all(),
            ['admin_name', 'handler', 'client_ip', 'created_time@=', 'tag']
        );

        $orders = ['id' => SORT_DESC];
        return $this->adminActionLogRepository->paginate($restrictions, [], $orders, Page::of($page, $size));
    }

    #[Authorize('user')]
    #[GetMapping]
    public function detailAction(int $id)
    {
        $adminActionLog = $this->adminActionLogRepository->get($id);
        if ($adminActionLog->admin_id === $this->identity->getId() || $this->authorization->isAllowed('detail')) {
            return $adminActionLog;
        } else {
            return '没有权限';
        }
    }

    #[Authorize('user')]
    #[ViewGetMapping]
    public function latestAction(int $page = 1, int $size = 10)
    {
        $restrictions = Restrictions::of($this->request->all(), ['handler', 'client_ip', 'created_time@=', 'tag']);
        $restrictions->eq('admin_id', $this->identity->getId());

        $orders = ['id' => SORT_DESC];
        return $this->adminActionLogRepository->paginate($restrictions, [], $orders, Page::of($page, $size));
    }
}