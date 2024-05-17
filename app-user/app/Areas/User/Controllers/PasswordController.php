<?php
declare(strict_types=1);

namespace App\Areas\User\Controllers;

use App\Controllers\Controller;
use App\Entities\User;
use App\Repositories\UserRepository;
use Exception;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Http\CaptchaInterface;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\InputInterface;
use ManaPHP\Http\Router\Attribute\PostMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mailing\MailerInterface;
use ManaPHP\Mvc\View\Attribute\ViewGetMapping;

#[Authorize('*')]
#[RequestMapping('/user/password')]
class PasswordController extends Controller
{
    #[Autowired] protected CaptchaInterface $captcha;
    #[Autowired] protected MailerInterface $mailer;
    #[Autowired] protected UserRepository $userRepository;

    #[Config] protected string $app_name;

    #[PostMapping]
    public function captchaAction()
    {
        return $this->captcha->generate();
    }

    public function forgetVars()
    {
        $this->view->setVar('redirect', $this->request->input('redirect', $this->router->createUrl('/')));

        return $this->view->setVar('user_name', $this->cookies->get('user_name'));
    }

    #[ViewGetMapping(vars: 'forgetVars'), PostMapping]
    public function forgetAction(InputInterface $input)
    {
        $user_name = $input->string('user_name');
        $email = $input->string('email');

        $user = $this->userRepository->first(['user_name' => $user_name]);
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

    public function resetVars(): array
    {
        $token = $this->request->input('token');

        try {
            $claims = jwt_decode($token, 'user.password.forget');
        } catch (Exception $exception) {
            return ['expired' => true, 'token' => $token];
        }

        return ['expired'   => false,
                'user_name' => $claims['user_name'],
                'token'     => $token,
        ];
    }

    #[ViewGetMapping(vars: 'resetVars'), PostMapping]
    public function resetAction(string $token, string $password)
    {
        try {
            $claims = jwt_decode($token, 'user.password.forget');
        } catch (Exception $exception) {
            return '重置失败：Token已过期';
        }

        $user_name = $claims['user_name'];

        $user = $this->userRepository->firstOrFail(['user_name' => $user_name]);
        $user->password = $password;

        $this->userRepository->update($user);

        return $this->response->json(['code' => 0, 'msg' => '重置密码成功']);
    }

    #[Authorize('user')]
    #[ViewGetMapping, PostMapping]
    public function changeAction(string $old_password, string $new_password, string $new_password_confirm)
    {
        $user = $this->userRepository->get($this->identity->getId());
        if (!$user->verifyPassword($old_password)) {
            return '旧密码不正确';
        }

        $user->password = $new_password;
        if ($new_password_confirm !== $user->password) {
            return '两次输入的密码不一致';
        }

        $this->userRepository->update($user);
        $this->session->destroy();
    }
}