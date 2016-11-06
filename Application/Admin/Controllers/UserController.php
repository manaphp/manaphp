<?php
namespace Application\Admin\Controllers;

use Application\Admin\Models\Admin;
use Application\Admin\Models\AdminDetail;
use Application\Admin\Models\AdminLogin;

class UserController extends ControllerBase
{
    public function captchaAction()
    {
        return $this->captcha->generate();
    }

    public function loginAction()
    {
        if ($this->request->isPost()) {
            $user_name = $this->request->get('user_name');
            $password = $this->request->get('password');
            $code = $this->request->get('code');

            $this->captcha->verify($code);

            if ($this->request->has('remember_me')) {
                $this->cookies->set('user_name', $user_name, strtotime('2 year'));
            } else {
                $this->cookies->delete('user_name');
            }

            $admin = Admin::findFirstByAdminName($user_name);
            if (!$admin || !$this->password->verify($password, $admin->password, $admin->salt)) {
                return $this->response->setJsonContent(['code' => __LINE__, 'error' => 'account or password is wrong.']);
            }

            $udid = $this->cookies->has('udid') ? $this->cookies->get('udid') : '';
            if (strlen($udid) !== 16) {
                $udid = $this->random->getBase(16);
                $this->cookies->set('udid', $udid, strtotime('5 year'), '/');
            }

            $adminLogin = new AdminLogin();

            $adminLogin->admin_id = $admin->admin_id;
            $adminLogin->ip = $this->request->getClientAddress();
            $adminLogin->udid = $udid;
            $adminLogin->user_agent = $this->request->getUserAgent();
            $adminLogin->login_time = time();
            $adminLogin->logout_time = 0;

            $adminLogin->create();

            $this->session->set('admin_auth', ['userId' => $admin->admin_id, 'userName' => $admin->admin_name]);
            $this->session->set('login_id', $adminLogin->login_id);

            return $this->response->setJsonContent(['code' => 0, 'error' => '']);
        } else {
            $this->view->setVar('redirect', $this->request->get('redirect', null, '/'));
            $this->view->setVar('user_name', $this->cookies->has('user_name') ? $this->cookies->get('user_name') : '');
        }
    }

    public function logoutAction()
    {
        $login_id = $this->session->get('login_id');
        if ($login_id) {
            $adminLogin = AdminLogin::findFirst(['login_id' => $login_id]);
            if ($adminLogin && !$adminLogin->logout_time) {
                $adminLogin->logout_time = time();
                $adminLogin->update();
            }
        }

        $this->session->destroy();

        return $this->response->redirect(['/', 'Home']);
    }

    public function registerAction()
    {
        if ($this->request->isAjax()) {
            $user_name = $this->request->get('user_name', 'account');
            $email = $this->request->get('email', 'email');
            $password = $this->request->get('password', 'password');
            $code = $this->request->get('code');

            $this->captcha->verify($code);

            if (Admin::exists(['admin_name' => $user_name])) {
                return $this->response->setJsonContent(['code' => __LINE__, 'error' => 'account already exists.']);
            }

            if (AdminDetail::exists(['email' => $email])) {
                return $this->response->setJsonContent(['code' => __LINE__, 'error' => 'email already exists.']);
            }

            $current_time = time();

            $admin = new Admin();

            $admin->admin_name = $user_name;
            $admin->salt = $this->password->salt();
            $admin->password = $this->password->hash($password, $admin->salt);
            $admin->created_time = $current_time;
            $admin->updated_time = $current_time;

            $admin->create();

            $adminDetail = new AdminDetail();

            $adminDetail->admin_id = $admin->admin_id;
            $adminDetail->admin_name = $admin->admin_name;
            $adminDetail->email = $email;
            $adminDetail->created_time = $current_time;
            $adminDetail->updated_time = $current_time;

            $adminDetail->create();

            return $this->response->setJsonContent(['code' => 0, 'error' => '']);
        }
    }
}