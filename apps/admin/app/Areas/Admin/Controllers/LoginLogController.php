<?php

namespace App\Areas\Admin\Controllers;

use App\Models\AdminLoginLog;
use ManaPHP\Mvc\Controller;

class LoginLogController extends Controller
{
    public function getAcl()
    {
        return ['*' => '@index', 'latest' => 'user'];
    }

    public function getVerbs()
    {
        return array_merge(parent::getVerbs(), [
            'latest' => 'GET'
        ]);
    }

    public function indexAction()
    {
        return AdminLoginLog::select(['login_id', 'admin_id', 'admin_name', 'client_udid', 'user_agent', 'client_ip', 'created_time'])
            ->orderBy(['login_id' => SORT_DESC])
            ->search(['admin_id', 'admin_name*=', 'client_ip', 'client_udid', 'created_time@='])
            ->paginate();
    }

    public function latestAction()
    {
        return AdminLoginLog::select(['login_id', 'client_udid', 'user_agent', 'client_ip', 'created_time'])
            ->search(['created_time@='])
            ->orderBy(['login_id' => SORT_DESC])
            ->where(['admin_id' => $this->identity->getId()])
            ->paginate();
    }
}