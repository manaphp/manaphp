<?php

namespace App\Areas\User\Controllers;

use App\Controllers\Controller;
use App\Models\UserActionLog;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\GetMapping;

#[Authorize('user')]
class ActionLogController extends Controller
{
    #[Authorize('user')]
    public function detailAction(UserActionLog $userActionLog)
    {
        if ($userActionLog->user_id == $this->identity->getId() || $this->authorization->isAllowed('detail')) {
            return $userActionLog;
        } else {
            return '没有权限';
        }
    }

    #[GetMapping]
    public function latestAction(int $page = 1, int $size = 10)
    {
        return UserActionLog::select()
            ->where(['user_id' => $this->identity->getId()])
            ->whereCriteria($this->request->all(), ['path', 'client_ip', 'created_time@=', 'tag'])
            ->orderBy(['id' => SORT_DESC])
            ->paginate($page, $size);
    }
}