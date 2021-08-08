<?php

namespace App\Areas\User\Controllers;

use App\Controllers\Controller;
use App\Models\User;
use ManaPHP\Helper\Str;
use App\Models\UserLoginLog;

/**
 * @property-read \ManaPHP\Configuration\Configure       $configure
 * @property-read \ManaPHP\Http\CaptchaInterface         $captcha
 */
class SessionController extends Controller
{
    public function getAcl()
    {
        return ['*' => '*'];
    }

    public function captchaAction()
    {
        return $this->captcha->generate();
    }

    public function loginView()
    {
        $this->view->setVar('redirect', input('redirect', $this->router->createUrl('/')));

        return $this->view->setVar('user_name', $this->cookies->get('user_name'));
    }

    public function loginAction()
    {
        if (!$udid = $this->cookies->get('CLIENT_UDID')) {
            $this->cookies->set('CLIENT_UDID', Str::random(16), strtotime('10 year'), '/');
        }

        if ($this->configure->env === 'prod') {
            $this->captcha->verify();
        } else {
            $this->session->remove('captcha');
        }

        $user = User::first(['user_name' => input('user_name')]);
        if (!$user || !$user->verifyPassword(input('password'))) {
            return '账号或密码不正确';
        }

        if ($user->status === User::STATUS_INIT) {
            return '账号还未激活';
        } elseif ($user->status === User::STATUS_LOCKED) {
            return '账号已锁定';
        }

        $claims = ['user_id' => $user->user_id, 'user_name' => $user->user_name, 'role' => 'user'];
        $this->identity->setClaims($claims);

        $session_id = $this->session->getId();
        if ($user->session_id && $session_id !== $user->session_id) {
            //同一个账号互踢
             $this->session->destroy($user->session_id);
        }

        $user->login_ip = $this->request->getClientIp();
        $user->login_time = time();
        $user->session_id = $session_id;
        $user->update();

        $adminLoginLog = new UserLoginLog();

        $adminLoginLog->user_id = $user->user_id;
        $adminLoginLog->user_name = $user->user_name;
        $adminLoginLog->client_ip = $this->request->getClientIp();
        $adminLoginLog->client_udid = $udid;
        $adminLoginLog->user_agent = $this->request->getUserAgent(255);

        $adminLoginLog->create();
    }

    public function logoutAction()
    {
        $this->session->destroy();

        return $this->response->redirect('/');
    }
}