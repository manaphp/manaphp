<?php
namespace Application\Admin\Controllers;

use Application\Admin\Models\AdminDetail;
use Application\Admin\Models\AdminLoginLog;
use Application\Admin\Models\Admin;

class UserController extends ControllerBase
{
    public function captchaAction()
    {
        return $this->captcha->generate();
    }

    public function loginAction()
    {
        if ($this->request->isPost()) {
            try {
                $user_name = $this->request->get('user_name', '*|account');
                $password = $this->request->get('password', '*');
                $code = $this->request->get('code', '*');
            } catch (\Exception $e) {
                return $this->response->setJsonContent(['code' => 1, 'message' => $e->getMessage()]);
            }

            try {
                $this->captcha->verify($code);
            } catch (\Exception $e) {
                return $this->response->setJsonContent(['code' => 2, 'message' => $e->getMessage()]);
            }

            if ($this->request->has('remember_me')) {
                $this->cookies->set('user_name', $user_name, strtotime('1 year'));
            } else {
                $this->cookies->delete('user_name');
            }

            $admin = Admin::findFirst(['admin_name' => $user_name]);
            if (!$admin || !$this->password->verify($password, $admin->password, $admin->salt)) {
                return $this->response->setJsonContent(['code' => 3, 'message' => 'account or password is wrong.']);
            }

            $admin->login_ip = $this->request->getClientAddress();
            $admin->login_time = time();
            $admin->update();

            $udid = $this->cookies->get('CLIENT_UDID', '');
            if (!$udid) {
                $udid = $this->random->getBase(32);
                $this->cookies->set('CLIENT_UDID', $udid, strtotime('10 year'), '/');
            }

            $adminLoginLog = new AdminLoginLog();

            $adminLoginLog->admin_id = $admin->admin_id;
            $adminLoginLog->admin_name = $user_name;
            $adminLoginLog->client_ip = $this->request->getClientAddress();
            $adminLoginLog->client_udid = $udid;
            $adminLoginLog->user_agent = $this->request->getUserAgent();
            $adminLoginLog->created_time = time();

            $adminLoginLog->create();

            $this->session->set('admin_auth', ['userId' => $admin->admin_id, 'userName' => $admin->admin_name]);

            return $this->response->setJsonContent(['code' => 0, 'message' => '']);
        } else {
            $this->view->setVar('redirect', $this->request->get('redirect', null, '/'));
            $this->view->setVar('user_name', $this->cookies->has('user_name') ? $this->cookies->get('user_name') : '');
        }
    }

    public function logoutAction()
    {
        $this->session->destroy();

        return $this->response->redirect(['/', 'Home']);
    }

    public function registerAction()
    {
        if ($this->request->isAjax()) {
            try {
                $user_name = $this->request->get('user_name', '*|account');
                $email = $this->request->get('email', '*|email');
                $password = $this->request->get('password', '*|password');
                $code = $this->request->get('code');
            } catch (\Exception $e) {
                return $this->response->setJsonContent(['code' => 1, 'message' => $e->getMessage()]);
            }

            try {
                $this->captcha->verify($code);
            } catch (\Exception $e) {
                return $this->response->setJsonContent(['code' => 2, 'message' => $e->getMessage()]);
            }

            if (Admin::exists(['admin_name' => $user_name])) {
                return $this->response->setJsonContent(['code' => 3, 'message' => 'account already exists.']);
            }

            if (Admin::exists(['email' => $email])) {
                return $this->response->setJsonContent(['code' => 3, 'message' => 'email already exists.']);
            }

            $admin = new Admin();

            $admin->admin_name = $user_name;
            $admin->email = $email;
            $admin->status = Admin::STATUS_ACTIVE;
            $admin->login_ip = '';
            $admin->login_time = 0;
            $admin->salt = $this->password->salt();
            $admin->password = $this->password->hash($password, $admin->salt);
            $admin->updated_time = $admin->created_time = time();

            $admin->create();

            return $this->response->setJsonContent(['code' => 0, 'message' => '']);
        }
    }
}