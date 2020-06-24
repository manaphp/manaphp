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

    public function getVerbs()
    {
        return array_merge(parent::getVerbs(), [
            'latest' => 'GET'
        ]);
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

    public function latestAction()
    {
        return AdminActionLog::select()
            ->where(['admin_id' => $this->identity->getId()])
            ->search(['path', 'client_ip', 'created_time@=', 'tag'])
            ->orderBy(['id' => SORT_DESC])
            ->paginate();
    }
}