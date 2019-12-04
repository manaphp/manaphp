<?php
namespace App\Areas\Admin\Controllers;

use App\Models\AdminActionLog;
use ManaPHP\Mvc\Controller;

class ActionLogController extends Controller
{
    public function getAcl()
    {
        return ['*' => '@index', 'latest' => 'user', 'detailSelf' => 'user'];
    }

    public function indexAction()
    {
        return $this->request->isAjax()
            ? AdminActionLog::select(['id', 'admin_name', 'client_ip', 'client_udid', 'method', 'path', 'url', 'created_time'])
                ->search(['admin_name', 'path', 'client_ip', 'created_time@='])
                ->orderBy(['id' => SORT_DESC])
                ->paginate()
            : null;
    }

    public function detailAction()
    {
        $log = AdminActionLog::rGet();

        if ($log->admin_id == $this->identity->getId() || $this->authorization->isAllowed('detail')) {
            return $log;
        } else {
            return '没有权限';
        }
    }

    public function latestAction()
    {
        return $this->request->isAjax()
            ? AdminActionLog::select(['id', 'client_ip', 'method', 'path', 'url', 'created_time'])
                ->where(['admin_id' => $this->identity->getId()])
                ->search(['path', 'client_ip', 'created_time@='])
                ->orderBy(['id' => SORT_DESC])
                ->paginate()
            : null;
    }
}