<?php

namespace App\Areas\User\Controllers;

use App\Controllers\Controller;
use App\Models\UserActionLog;

class ActionLogController extends Controller
{
    public function getAcl()
    {
        return ['*' => '@index', 'latest' => 'user', 'detail' => 'user'];
    }

    public function getVerbs()
    {
        return array_merge(
            parent::getVerbs(), [
                'latest' => 'GET'
            ]
        );
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

    public function latestAction()
    {
        return UserActionLog::select()
            ->where(['user_id' => $this->identity->getId()])
            ->search(['path', 'client_ip', 'created_time@=', 'tag'])
            ->orderBy(['id' => SORT_DESC])
            ->paginate();
    }
}