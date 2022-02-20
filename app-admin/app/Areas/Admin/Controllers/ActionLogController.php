<?php

namespace App\Areas\Admin\Controllers;

use App\Controllers\Controller;
use App\Models\AdminActionLog;
use ManaPHP\Http\Controller\Attribute\AcceptVerbs;

class ActionLogController extends Controller
{
    public function getAcl(): array
    {
        return ['*' => '@index', 'latest' => 'user', 'detailSelf' => 'user'];
    }

    public function indexAction()
    {
        return AdminActionLog::select()
            ->search(['admin_name', 'path', 'client_ip', 'created_time@=', 'tag'])
            ->orderBy(['id' => SORT_DESC])
            ->paginate();
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

    #[AcceptVerbs(['GET'])]
    public function latestAction()
    {
        return AdminActionLog::select()
            ->where(['admin_id' => $this->identity->getId()])
            ->search(['path', 'client_ip', 'created_time@=', 'tag'])
            ->orderBy(['id' => SORT_DESC])
            ->paginate();
    }
}