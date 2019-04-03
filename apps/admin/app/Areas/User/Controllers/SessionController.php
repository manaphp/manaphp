<?php
namespace App\Areas\User\Controllers;

use App\Areas\Rbac\Models\AdminRole;
use App\Areas\Rbac\Models\Role;
use App\Models\Admin;
use App\Models\AdminLoginLog;
use ManaPHP\Mvc\Controller;

class SessionController extends Controller
{
    public function getAcl()
    {
        return ['*' => '*'];
    }

    public function captchaAction()
    {
        return $this->captcha->generate();
    }

    public function loginAction()
    {
        if (!$udid = $this->cookies->get('CLIENT_UDID')) {
            $udid = $this->random->getBase(16);
            $this->cookies->set('CLIENT_UDID', $udid, strtotime('10 year'), '/');
        }

        if ($this->request->isPost()) {
            if (!$this->configure->debug) {
                $this->captcha->verify();
            }

            $admin = Admin::first(['admin_name' => input('user_name')]);
            if (!$admin || $admin->verifyPassword(input('password'))) {
                return $this->response->setJsonError('account or password is wrong.');
            }

            if ($this->request->has('remember_me')) {
                $this->cookies->set('user_name', $admin->admin_name, strtotime('1 year'));
            } else {
                $this->cookies->delete('user_name');
            }

            if ($admin->admin_id === 1) {
                $roles = ['admin'];
            } else {
                $roles = AdminRole::values('role_name', ['admin_id' => $admin->admin_id]);
                $roles = Role::values('role_name', ['enabled' => 1, 'role_name' => $roles]);
            }

            $claims = ['admin_id' => $admin->admin_id, 'admin_name' => $admin->admin_name, 'role' => implode(',', $roles)];
            $this->identity->setClaims($claims);

            $admin->login_ip = $this->request->getClientIp();
            $admin->login_time = time();
            $admin->update();

            $adminLoginLog = new AdminLoginLog();

            $adminLoginLog->admin_id = $admin->admin_id;
            $adminLoginLog->admin_name = $admin->admin_name;
            $adminLoginLog->client_ip = $this->request->getClientIp();
            $adminLoginLog->client_udid = $udid;
            $adminLoginLog->user_agent = $this->request->getUserAgent();

            $adminLoginLog->create();

            return 0;
        } else {
            $this->view->setVar('redirect', input('redirect', $this->router->createUrl('/')));

            return $this->view->setVar('user_name', $this->cookies->get('user_name'));
        }
    }

    public function logoutAction()
    {
        $this->session->destroy();

        return $this->response->redirect('/');
    }
}