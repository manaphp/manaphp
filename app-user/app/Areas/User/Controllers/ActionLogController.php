<?php
declare(strict_types=1);

namespace App\Areas\User\Controllers;

use App\Controllers\Controller;
use App\Models\UserActionLog;
use App\Repositories\UserActionLogRepository;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\GetMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mvc\View\Attribute\ViewGetMapping;
use ManaPHP\Persistence\Restrictions;

#[Authorize('user')]
#[RequestMapping('/user/action-log')]
class ActionLogController extends Controller
{
    #[Autowired] protected UserActionLogRepository $userActionLogRepository;

    #[Authorize('user')]
    #[GetMapping]
    public function detailAction(int $id)
    {
        $userActionLog = $this->userActionLogRepository->get($id);

        if ($userActionLog->user_id == $this->identity->getId() || $this->authorization->isAllowed('detail')) {
            return $userActionLog;
        } else {
            return '没有权限';
        }
    }

    #[ViewGetMapping]
    public function latestAction(int $page = 1, int $size = 10)
    {
        return UserActionLog::select()
            ->where(['user_id' => $this->identity->getId()])
            ->where(Restrictions::of($this->request->all(), ['path', 'client_ip', 'created_time@=', 'tag']))
            ->orderBy(['id' => SORT_DESC])
            ->paginate($page, $size);
    }
}