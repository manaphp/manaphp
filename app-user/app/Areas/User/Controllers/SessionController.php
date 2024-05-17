<?php
declare(strict_types=1);

namespace App\Areas\User\Controllers;

use App\Controllers\Controller;
use App\Entities\User;
use App\Entities\UserLoginLog;
use App\Repositories\AdminLoginLogRepository;
use App\Repositories\UserRepository;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Helper\Str;
use ManaPHP\Http\CaptchaInterface;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\GetMapping;
use ManaPHP\Http\Router\Attribute\PostMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mvc\View\Attribute\ViewGetMapping;
use function substr;

#[Authorize('*')]
#[RequestMapping('/user/session')]
class SessionController extends Controller
{
    #[Autowired] protected CaptchaInterface $captcha;
    #[Autowired] protected UserRepository $userRepository;
    #[Autowired] protected AdminLoginLogRepository $adminLoginLogRepository;

    #[Config] protected string $app_env;

    #[PostMapping]
    public function captchaAction()
    {
        return $this->captcha->generate();
    }

    public function loginVars(): array
    {
        $vars = [];
        $vars['redirect'] = $this->request->input('redirect', $this->router->createUrl('/'));
        $vars['user_name'] = $this->cookies->get('user_name');

        return $vars;
    }

    #[ViewGetMapping('/login', vars: 'loginVars'), PostMapping('/login')]
    public function loginAction(string $code, string $user_name, string $password)
    {
        if (!$udid = $this->cookies->get('CLIENT_UDID')) {
            $this->cookies->set('CLIENT_UDID', Str::random(16), strtotime('10 year'), '/');
        }

        if ($this->app_env === 'prod') {
            $this->captcha->verify($code);
        } else {
            $this->session->remove('captcha');
        }

        $user = $this->userRepository->first(['user_name' => $user_name]);
        if (!$user || !$user->verifyPassword($password)) {
            return '账号或密码不正确';
        }

        if ($user->status === User::STATUS_INIT) {
            return '账号还未激活';
        } elseif ($user->status === User::STATUS_LOCKED) {
            return '账号已锁定';
        }

        $claims = ['user_id' => $user->user_id, 'user_name' => $user->user_name, 'role' => 'user'];
        $this->identity->set($claims);

        $session_id = $this->session->getId();
        if ($user->session_id && $session_id !== $user->session_id) {
            //同一个账号互踢
            $this->session->destroy($user->session_id);
        }

        $user->login_ip = $this->request->ip();
        $user->login_time = time();
        $user->session_id = $session_id;

        $this->userRepository->update($user);

        $adminLoginLog = new UserLoginLog();

        $adminLoginLog->user_id = $user->user_id;
        $adminLoginLog->user_name = $user->user_name;
        $adminLoginLog->client_ip = $this->request->ip();
        $adminLoginLog->client_udid = $udid;
        $adminLoginLog->user_agent = substr($this->request->header('user-agent'), 0, 255);

        $this->adminLoginLogRepository->create($adminLoginLog);
    }

    #[GetMapping(['/logout', '/user/session/logout'])]
    public function logoutAction()
    {
        $this->session->destroy();

        return $this->response->redirect('/');
    }
}