<?php
namespace App\Areas\User\Controllers;

use App\Areas\User\Services\ResetPasswordTokenService;
use App\Models\Admin;
use ManaPHP\Mvc\Controller;

class AccountController extends Controller
{
    public function getAcl()
    {
        return ['*' => 'user', 'resetPassword' => '*'];
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

            $old_password = input('old_password');
            $new_password = input('new_password');
            $admin = Admin::get($this->identity->getId());
            if (!$this->password->verify($old_password, $admin->password, $admin->salt)) {
                return $this->response->setJsonError('旧密码不正确');
            }

            $admin->salt = $this->password->salt();
            $admin->password = $this->password->hash($new_password, $admin->salt);
            $admin->update();
            $this->session->destroy();
            return 0;
        }
    }

    public function resetPasswordAction(ResetPasswordTokenService $resetPasswordTokenService)
    {
        if ($this->request->isPost()) {
            if (!$this->configure->debug) {
                $this->captcha->verify();
            }

            $jwt = $resetPasswordTokenService->verify(input('token'));
            if (!$jwt) {
                return '已过期或无效';
            }

            $password = input('password');
            $admin = Admin::first(['admin_name' => $jwt['admin_name']]);

            $admin->salt = $this->password->salt();
            $admin->password = $this->password->hash($password, $admin->salt);
            $admin->update();
            $this->session->destroy();
            return $this->response->setJsonContent(0);
        } else {
            if ($this->identity->getRole() === 'admin') {
                return $this->response->setJsonData($resetPasswordTokenService->generate(input('admin_name')));
            }
        }
    }
}