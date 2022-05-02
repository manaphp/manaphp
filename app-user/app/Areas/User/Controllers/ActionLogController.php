<?php

namespace App\Areas\User\Controllers;

use App\Controllers\Controller;
use App\Models\UserActionLog;
use ManaPHP\Http\Controller\Attribute\AcceptVerbs;
use ManaPHP\Http\Controller\Attribute\Authorize;

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

    #[AcceptVerbs(['GET'])]
    public function latestAction()
    {
        return UserActionLog::select()
            ->where(['user_id' => $this->identity->getId()])
            ->search(['path', 'client_ip', 'created_time@=', 'tag'])
            ->orderBy(['id' => SORT_DESC])
            ->paginate();
    }
}