<?php
namespace App\Areas\User\Controllers;

use App\Models\AdminActionLog;
use ManaPHP\Mvc\Controller;

class ActionLogController extends Controller
{
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
}