<?php

namespace App\Areas\User\Controllers;

use App\Controllers\Controller;
use App\Models\User;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Http\CaptchaInterface;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Mailing\MailerInterface;

#[Authorize('*')]
class PasswordController extends Controller
{
    #[Autowired] protected CaptchaInterface $captcha;
    #[Autowired] protected MailerInterface $mailer;

    #[Config] protected string $app_name;

    public function captchaAction()
    {
        return $this->captcha->generate();
    }

    public function forgetView()
    {
        $this->view->setVar('redirect', $this->request->input('redirect', $this->router->createUrl('/')));

        return $this->view->setVar('user_name', $this->cookies->get('user_name'));
    }

    public function forgetAction()
    {
        $user_name = input('user_name');
        $email = input('email');

        $user = User::first(['user_name' => $user_name]);
        if (!$user || $user->email !== $email) {
            return '账号不存在或账号与邮箱不匹配';
        }

        $token = jwt_encode(['user_name' => $user_name], 600, 'user.password.forget');

        $this->mailer->compose()
            ->setSubject($this->app_name . '-重置密码邮件')
            ->setTo($email)
            ->setHtmlBody(
                ['@app/Areas/User/Views/Mail/ResetPassword', 'email' => $email, 'user_name' => $user_name,
                 'token'                                             => $token]
            )
            ->send();
        return $this->response->json(['code' => 0, 'msg' => '重置密码连接已经发送到您的邮箱']);
    }

    public function resetView()
    {
        $token = input('token');

        try {
            $claims = jwt_decode($token, 'user.password.forget');
        } catch (\Exception $exception) {
            return $this->view->setVars(['expired' => true, 'token' => $token]);
        }

        return $this->view->setVars(
            [
                'expired'   => false,
                'user_name' => $claims['user_name'],
                'token'     => $token,
            ]
        );
    }

    public function resetAction()
    {
        try {
            $claims = jwt_decode(input('token'), 'user.password.forget');
        } catch (\Exception $exception) {
            return '重置失败：Token已过期';
        }

        $user_name = $claims['user_name'];

        $user = User::firstOrFail(['user_name' => $user_name]);
        $user->password = input('password');
        $user->update();

        return $this->response->json(['code' => 0, 'msg' => '重置密码成功']);
    }

    #[Authorize('user')]
    public function changeAction()
    {
        $user = User::get($this->identity->getId());
        if (!$user->verifyPassword(input('old_password'))) {
            return '旧密码不正确';
        }

        $user->password = input('new_password');
        if (input('new_password_confirm') !== $user->password) {
            return '两次输入的密码不一致';
        }

        $user->update();
        $this->session->destroy();
    }
}