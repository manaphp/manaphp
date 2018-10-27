<?php
namespace App\Areas\User\Controllers;

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
        if ($this->request->isPost()) {
            $this->captcha->verify();

            try {
                $user_name = $this->request->get('user_name', '*|account');
                $password = $this->request->get('password', '*');
            } catch (\Exception $e) {
                return $this->response->setJsonContent($e);
            }

            if ($this->request->has('remember_me')) {
                $this->cookies->set('user_name', $user_name, strtotime('1 year'));
            } else {
                $this->cookies->delete('user_name');
            }

            $admin = Admin::first(['admin_name' => $user_name]);
            if (!$admin || !$this->password->verify($password, $admin->password, $admin->salt)) {
                return $this->response->setJsonContent('account or password is wrong.');
            }

            $admin->login_ip = $this->request->getClientIp();
            $admin->login_time = time();
            $admin->update();

            $udid = $this->cookies->get('CLIENT_UDID', '');
            if (!$udid) {
                $udid = $this->random->getBase(16);
                $this->cookies->set('CLIENT_UDID', $udid, strtotime('10 year'), '/');
            }

            $adminLoginLog = new AdminLoginLog();

            $adminLoginLog->admin_id = $admin->admin_id;
            $adminLoginLog->admin_name = $user_name;
            $adminLoginLog->client_ip = $this->request->getClientIp();
            $adminLoginLog->client_udid = $udid;
            $adminLoginLog->user_agent = $this->request->getUserAgent();

            $adminLoginLog->create();

            $this->session->set('auth', ['admin_id' => $admin->admin_id, 'admin_name' => $admin->admin_name]);

            return $this->response->setJsonContent(0);
        } else {
            $this->view->setVar('redirect', $this->request->get('redirect', null, '/'));
            $this->view->setVar('user_name', $this->cookies->has('user_name') ? $this->cookies->get('user_name') : '');
        }
    }

    public function logoutAction()
    {
        $this->session->destroy();

        return $this->response->redirect('/');
    }

    public function logAction()
    {
        if ($this->request->isAjax()) {
            $builder = AdminLoginLog::criteria()
                ->select(['login_id', 'admin_id', 'admin_name', 'client_udid', 'user_agent', 'client_ip', 'created_time'])
                ->orderBy('login_id DESC');

            $builder->whereSearch(['admin_id', 'admin_name*=', 'client_ip', 'created_time@=']);

            return $this->response->setJsonContent($builder->paginate(20));
        }
    }
}