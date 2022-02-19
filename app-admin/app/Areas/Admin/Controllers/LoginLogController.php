<?php

namespace App\Areas\Admin\Controllers;

use App\Controllers\Controller;
use App\Models\AdminLoginLog;

class LoginLogController extends Controller
{
    public function getAcl(): array
    {
        return ['*' => '@index', 'latest' => 'user'];
    }

    public function getVerbs(): array
    {
        return array_merge(
            parent::getVerbs(), [
                'latest' => 'GET'
            ]
        );
    }

    public function indexAction()
    {
        return AdminLoginLog::select(
            ['login_id', 'admin_id', 'admin_name', 'client_udid', 'user_agent', 'client_ip', 'created_time']
        )
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