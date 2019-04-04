<?php
namespace App\Areas\User\Controllers;

use App\Models\Admin;
use ManaPHP\Mvc\Controller;

class PasswordController extends Controller
{
    public function getAcl()
    {
        return ['*' => 'user', 'reset' => '*', 'forget' => '*', 'captcha' => '*'];
    }

    public function captchaAction()
    {
        return $this->captcha->generate();
    }

    public function forgetAction()
    {
        if ($this->request->isPost()) {
            $user_name = input('user_name');
            $email = input('email');

            $admin = Admin::first(['admin_name' => $user_name]);
            if (!$admin || $admin->email !== $email) {
                return $this->response->setJsonError('账号不存在或账号与邮箱不匹配');
            }
            return $this->response->setJsonData(jwt_encode(['user_name' => $user_name, 'scope' => 'admin.user.password.forget'], 300), '重置密码链接已发到您的邮箱');
        } else {
            $this->view->setVar('redirect', input('redirect', $this->router->createUrl('/')));

            return $this->view->setVar('user_name', $this->cookies->get('user_name'));
        }
    }

    public function resetAction()
    {
        if ($this->request->isAjax()) {
            $claims = jwt_decode(input('token'), 'admin.user.password.forget');

            $user_name = $claims['user_name'];

            $admin = Admin::firstOrFail(['admin_name' => $user_name]);
            $admin->password = input('password');
            $admin->update();

            return $this->response->setJsonData([], '重置密码成功');
        } else {
            $claims = jwt_decode(input('token'), 'admin.user.password.forget');
        }
    }

    public function changeAction()
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
}