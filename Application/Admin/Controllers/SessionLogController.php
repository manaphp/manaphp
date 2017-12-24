<?php
namespace Application\Admin\Controllers;

use Application\Admin\Models\AdminLoginLog;

class SessionLogController extends ControllerBase
{
    public function indexAction()
    {
        if ($this->request->isAjax()) {
            $builder = AdminLoginLog::query()
                ->select('login_id, admin_id, admin_name, client_udid, user_agent, client_ip, created_time')
                ->orderBy('login_id DESC');

            $builder->whereRequest(['admin_id', 'admin_name*=', 'client_ip', 'created_time~=']);
            $builder->paginate(15);
            return $this->response->setJsonContent(['code' => 0, 'message' => '', 'data' => $this->paginator]);
        }
    }
}