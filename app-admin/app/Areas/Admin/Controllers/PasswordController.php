<?php
declare(strict_types=1);

namespace App\Areas\Admin\Controllers;

use App\Controllers\Controller;
use App\Models\Admin;
use Exception;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Http\CaptchaInterface;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\GetMapping;
use ManaPHP\Http\Router\Attribute\PostMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mailing\MailerInterface;
use ManaPHP\Mvc\View\Attribute\View;
use ManaPHP\Mvc\ViewInterface;

#[Authorize('*')]
#[RequestMapping('/admin/password')]
class PasswordController extends Controller
{
    #[Autowired] protected ViewInterface $view;
    #[Autowired] protected CaptchaInterface $captcha;
    #[Autowired] protected MailerInterface $mailer;

    #[Config] protected string $app_name;

    #[PostMapping]
    public function captchaAction()
    {
        return $this->captcha->generate();
    }

    public function forgetVars(): array
    {
        $vars = [];

        $vars['redirect'] = $this->request->input('redirect', $this->router->createUrl('/'));
        $vars['admin_name'] = $this->cookies->get('admin_name');

        return $vars;
    }

    #[View(vars: 'forgetVars')]
    #[GetMapping, PostMapping]
    public function forgetAction(string $admin_name, string $email)
    {
        $admin = Admin::first(['admin_name' => $admin_name]);
        if (!$admin || $admin->email !== $email) {
            return '账号不存在或账号与邮箱不匹配';
        }

        $token = jwt_encode(['admin_name' => $admin_name], 600, 'admin.password.forget');

        $this->mailer->compose()
            ->setSubject($this->app_name . '-重置密码邮件')
            ->setTo($email)
            ->setHtmlBody(
                ['@app/Areas/Admin/Views/Mail/ResetPassword', 'email' => $email, 'admin_name' => $admin_name,
                 'token'                                              => $token]
            )
            ->send();
        return $this->response->json(['code' => 0, 'msg' => '重置密码连接已经发送到您的邮箱']);
    }

    public function resetVars(string $token): array
    {
        try {
            $claims = jwt_decode($token, 'admin.password.forget');
        } catch (Exception $exception) {
            return ['expired' => true, 'token' => $token];
        }

        return ['expired'    => false,
                'admin_name' => $claims['admin_name'],
                'token'      => $token,
        ];
    }

    #[View(vars: 'resetVars')]
    #[GetMapping, PostMapping]
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

        return $this->response->json(['code' => 0, 'msg' => '重置密码成功']);
    }

    #[Authorize('user')]
    #[View]
    #[GetMapping, PostMapping]
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