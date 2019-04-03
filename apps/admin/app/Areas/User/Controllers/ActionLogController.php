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
                ->select(['id', 'user_name', 'ip', 'udid', 'method', 'url', 'created_time'])
                ->whereSearch(['user_name', 'url'])
                ->orderBy(['id' => SORT_DESC])
                ->paginate(20)
            : null;
    }

    public function detailAction()
    {
        return AdminActionLog::get(input('id'));
    }
}