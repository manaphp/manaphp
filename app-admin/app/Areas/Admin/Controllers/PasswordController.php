<?php
declare(strict_types=1);

namespace App\Areas\Admin\Controllers;

use App\Controllers\Controller;
use App\Models\Admin;
use Exception;
use ManaPHP\Di\ConfigInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\CaptchaInterface;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Mailing\MailerInterface;
use ManaPHP\Mvc\ViewInterface;

#[Authorize('*')]
class PasswordController extends Controller
{
    #[Autowired] protected ViewInterface $view;
    #[Autowired] protected ConfigInterface $config;
    #[Autowired] protected CaptchaInterface $captcha;
    #[Autowired] protected MailerInterface $mailer;

    public function captchaAction()
    {
        return $this->captcha->generate();
    }

    public function forgetView()
    {
        $this->view->setVar('redirect', input('redirect', $this->router->createUrl('/')));

        return $this->view->setVar('admin_name', $this->cookies->get('admin_name'));
    }

    public function forgetAction(string $admin_name, string $email)
    {
        $admin = Admin::first(['admin_name' => $admin_name]);
        if (!$admin || $admin->email !== $email) {
            return '账号不存在或账号与邮箱不匹配';
        }

        $token = jwt_encode(['admin_name' => $admin_name], 600, 'admin.password.forget');

        $this->mailer->compose()
            ->setSubject($this->config->get('name') . '-重置密码邮件')
            ->setTo($email)
            ->setHtmlBody(
                ['@app/Areas/Admin/Views/Mail/ResetPassword', 'email' => $email, 'admin_name' => $admin_name,
                 'token'                                              => $token]
            )
            ->send();
        return $this->response->setJsonOk('重置密码连接已经发送到您的邮箱');
    }

    public function resetView(string $token)
    {
        try {
            $claims = jwt_decode($token, 'admin.password.forget');
        } catch (Exception $exception) {
            return $this->view->setVars(['expired' => true, 'token' => $token]);
        }

        return $this->view->setVars(
            [
                'expired'    => false,
                'admin_name' => $claims['admin_name'],
                'token'      => $token,
            ]
        );
    }

    public function resetAction(string $token, string $password)
    {
        try {
            $claims = jwt_decode($token, 'admin.password.forget');
        } catch (Exception $exception) {
            return '重置失败：Token已过期';
        }

        $admin_name = $claims['admin_name'];

        $admin = Admin::firstOrFail(['admin_name' => $admin_name]);
        $admin->password = $password;
        $admin->update();

        return $this->response->setJsonOk('重置密码成功');
    }

    #[Authorize('user')]
    public function changeAction(string $old_password, string $new_password, string $new_password_confirm)
    {
        $admin = Admin::get($this->identity->getId());
        if (!$admin->verifyPassword($old_password)) {
            return '旧密码不正确';
        }

        $admin->password = $new_password;
        if ($new_password_confirm !== $admin->password) {
            return '两次输入的密码不一致';
        }

        $admin->update();
        $this->session->destroy();
    }
}