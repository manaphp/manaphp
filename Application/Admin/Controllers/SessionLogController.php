<?php
namespace Application\Admin\Controllers;

use Application\Admin\Models\AdminDetail;
use Application\Admin\Models\AdminLogin;

class SessionLogController extends ControllerBase
{
    public function indexAction()
    {
        $date_range = $this->request->get('date_range');
        $page = $this->request->get('page', 'int', 1);

        $builder = $this->modelsManager->createBuilder()
            ->columns('al.login_id, al.admin_id, ad.admin_name, al.udid, al.user_agent, al.ip, al.login_time, al.logout_time')
            ->addFrom(AdminLogin::class, 'al')
            ->leftJoin(AdminDetail::class, 'ad.admin_id =al.admin_id', 'ad')
            ->orderBy('al.login_id DESC');

        $parts = explode(' - ', $date_range);
        if (count($parts) === 2) {
            $date_start = strtotime($parts[0]);
            $date_end = strtotime($parts[1]);
            if ($date_start && $date_end) {
                $builder->betweenWhere('al.login_time', $date_start, $date_end + 3600 * 24);
            }
        }
        $admin_logins = [];

        $builder->paginate(15, $page);

        foreach ($this->paginator->items as $row) {
            $admin_login = [];
            $admin_login['login_id'] = $row['login_id'];
            $admin_login['admin_id'] = $row['admin_id'];
            $admin_login['admin_name'] = $row['admin_name'];
            $admin_login['udid'] = $row['udid'];
            $admin_login['ip'] = $row['ip'];
            $admin_login['user_agent'] = $row['user_agent'];
            $admin_login['login_time'] = $row['login_time'];
            $admin_login['logout_time'] = $row['logout_time'];

            $admin_logins[] = $admin_login;
        }

        $this->view->setVar('admin_logins', $admin_logins);
    }

    public function detailAction()
    {
        $login_id = $this->request->get('login_id');

        $builder = $this->modelsManager->createBuilder()
            ->columns('al.login_id, al.admin_id, al.udid, al.ip, al.user_agent, al.login_time, al.logout_time')
            ->addFrom(AdminLogin::class, 'al')
            ->where('al.login_id', $login_id);

        $rows = $builder->execute();
        if (count($rows) === 1) {
            $row = $rows[0];

            $admin_login = [];
            $admin_login['login_id'] = $row['login_id'];
            $admin_login['admin_id'] = $row['admin_id'];
            $admin_login['ip'] = $row['ip'];
            $admin_login['udid'] = $row['udid'];
            $admin_login['user_agent'] = $row['user_agent'];
            $admin_login['login_time'] = $row['login_time'];
            $admin_login['logout_time'] = $row['logout_time'];
        } else {
            $admin_login = [];
        }

        return $this->response->setJsonContent(['code' => 0, 'error' => '', 'data' => ['admin_login' => $admin_login]]);
    }
}