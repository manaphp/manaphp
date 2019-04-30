<?php
namespace App\Areas\User\Controllers;

use App\Models\AdminActionLog;
use ManaPHP\Mvc\Controller;

class ActionLogController extends Controller
{
    public function getAcl()
    {
        return ['latest' => 'user', 'detail' => '@index', 'detailSelf' => 'user'];
    }

    public function indexAction()
    {
        return $this->request->isAjax()
            ? AdminActionLog::query()
                ->select(['id', 'user_name', 'client_ip', 'client_udid', 'method', 'path', 'created_time'])
                ->whereSearch(['user_name', 'path', 'client_ip', 'created_time@='])
                ->orderBy(['id' => SORT_DESC])
                ->paginate()
            : null;
    }

    public function detailAction()
    {
        return AdminActionLog::firstOrNull();
    }

    public function detailSelfAction()
    {
        $log = AdminActionLog::firstOrNull();
        if ($log->user_id != $this->identity->getId()) {
            return '没有权限';
        }

        return $log;
    }

    public function latestAction()
    {
        return $this->request->isAjax()
            ? AdminActionLog::query()
                ->select(['id', 'client_ip', 'method', 'path', 'url', 'created_time'])
                ->where('user_id', $this->identity->getId())
                ->whereSearch(['path', 'client_ip', 'created_time@='])
                ->orderBy(['id' => SORT_DESC])
                ->paginate()
            : null;
    }
}