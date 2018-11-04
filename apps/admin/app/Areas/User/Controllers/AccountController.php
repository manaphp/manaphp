<?php
namespace App\Areas\User\Controllers;

use App\Models\Admin;
use ManaPHP\Mvc\Controller;

class AccountController extends Controller
{
    public function getAcl()
    {
        return ['*' => 'user'];
    }

    public function captchaAction()
    {
        return $this->captcha->generate();
    }

    public function registerAction()
    {
        if ($this->request->isAjax()) {
            $this->captcha->verify();

            $admin = Admin::newOrFail();

            $admin->status = Admin::STATUS_ACTIVE;
            $admin->login_ip = '';
            $admin->login_time = 0;
            $admin->salt = $this->password->salt();
            $admin->password = $this->password->hash($admin->password, $admin->salt);

            $admin->create();

            return 0;
        }
    }

    public function changePasswordAction()
    {
        if ($this->request->isPost()) {
            if (!$this->configure->debug) {
                $this->captcha->verify();
            }

            try {
                $old_password = $this->request->get('old_password', '*');
                $new_password = $this->request->get('new_password', 'length:5-16');
            } catch (\Exception $e) {
                return $this->response->setJsonContent($e);
            }
            $admin = Admin::first(['admin_id' => $this->identity->getId()]);
            if (!$admin || !$this->password->verify($old_password, $admin->password, $admin->salt)) {
                return $this->response->setJsonError('旧密码不正确');
            }

            $admin->salt = $this->password->salt();
            $admin->password = $this->password->hash($new_password, $admin->salt);
            $admin->update();
            $this->session->destroy();
            return $this->response->setJsonContent(0);
        }
    }
}