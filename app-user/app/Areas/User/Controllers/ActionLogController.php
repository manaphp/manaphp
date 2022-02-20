<?php

namespace App\Areas\User\Controllers;

use App\Controllers\Controller;
use App\Models\UserActionLog;
use ManaPHP\Http\Controller\Attribute\AcceptVerbs;

class ActionLogController extends Controller
{
    public function getAcl()
    {
        return ['*' => '@index', 'latest' => 'user', 'detail' => 'user'];
    }

    public function detailAction()
    {
        $log = UserActionLog::rGet();

        if ($log->user_id == $this->identity->getId() || $this->authorization->isAllowed('detail')) {
            return $log;
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