<?php
namespace App\Areas\Admin\Controllers;

use App\Areas\Rbac\Models\AdminRole;
use App\Areas\Rbac\Models\Role;
use App\Models\Admin;
use App\Models\AdminLoginLog;
use ManaPHP\Helper\Ip;
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

    public function loginView()
    {
        $this->view->setVar('redirect', input('redirect', $this->router->createUrl('/')));

        return $this->view->setVar('admin_name', $this->cookies->get('admin_name'));
    }

    public function loginAction()
    {
        if (!$udid = $this->cookies->get('CLIENT_UDID')) {
            $udid = $this->random->getBase(16);
            $this->cookies->set('CLIENT_UDID', $udid, strtotime('10 year'), '/');
        }

        if ($this->configure->env !== 'prod') {
            $this->session->remove('captcha');
        } else {
            $this->captcha->verify();
        }

        $admin = Admin::first(['admin_name' => input('admin_name')]);
        if (!$admin || !$admin->verifyPassword(input('password'))) {
            return '账号或密码不正确';
        }

        if ($admin->status === Admin::STATUS_INIT) {
            return '账号还未激活';
        } elseif ($admin->status === Admin::STATUS_LOCKED) {
            return '账号已锁定';
        }

        $client_ip = $this->request->getClientIp();

        if (!Ip::contains($admin->white_ip, $client_ip)) {
            return "$client_ip 地址未在白名单";
        }

        if ($admin->admin_id === 1) {
            $roles = ['admin'];
        } else {
            $roles = AdminRole::values('role_name', ['admin_id' => $admin->admin_id]);
            $roles = Role::values('role_name', ['enabled' => 1, 'role_name' => $roles]);
        }

        $claims = ['admin_id' => $admin->admin_id, 'admin_name' => $admin->admin_name, 'role' => implode(',', $roles)];
        $this->identity->setClaims($claims);

        $session_id = $this->session->getId();
        if ($admin->session_id && $session_id !== $admin->session_id) {
            //同一个账号互踢
            // $this->session->destroy($admin->session_id);
        }

        $admin->login_ip = $client_ip;
        $admin->login_time = time();
        $admin->session_id = $session_id;
        $admin->update();

        $adminLoginLog = new AdminLoginLog();

        $adminLoginLog->admin_id = $admin->admin_id;
        $adminLoginLog->admin_name = $admin->admin_name;
        $adminLoginLog->client_ip = $client_ip;
        $adminLoginLog->client_udid = $udid;
        $adminLoginLog->user_agent = $this->request->getUserAgent(255);

        $adminLoginLog->create();
    }

    public function logoutAction()
    {
        $this->session->destroy();

        return $this->response->redirect('/');
    }
}