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
            return Admin::createOrNull();
        }
    }

    public function changePasswordAction()
    {
        if ($this->request->isPost()) {
            if (!$this->configure->debug) {
                $this->captcha->verify();
            }

            $admin = Admin::get($this->identity->getId());
            if (!$admin->verifyPassword(input('old_password'))) {
                return '旧密码不正确';
            }

            $admin->password = input('new_password');

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

            $admin = Admin::firstOrFail(['admin_name' => $jwt['admin_name']]);

            $admin->password = input('password');
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