<?php
declare(strict_types=1);

namespace App\Areas\Admin\Controllers;

use App\Areas\Rbac\Models\AdminRole;
use App\Areas\Rbac\Models\Role;
use App\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminLoginLog;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Helper\Ip;
use ManaPHP\Helper\Str;
use ManaPHP\Http\CaptchaInterface;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\GetMapping;
use ManaPHP\Http\Router\Attribute\PostMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;

#[Authorize('*')]
#[RequestMapping('/admin/session')]
class SessionController extends Controller
{
    #[Autowired] protected CaptchaInterface $captcha;

    #[Config] protected string $app_env;

    #[PostMapping]
    public function captchaAction()
    {
        return $this->captcha->generate();
    }

    public function loginView()
    {
        $this->view->setVar('redirect', $this->request->input('redirect', $this->router->createUrl('/')));

        return $this->view->setVar('admin_name', $this->cookies->get('admin_name'));
    }

    #[GetMapping('/login'), PostMapping('/login')]
    public function loginAction(string $code, string $admin_name, string $password)
    {
        if (!$udid = $this->cookies->get('CLIENT_UDID')) {
            $this->cookies->set('CLIENT_UDID', Str::random(16), strtotime('10 year'), '/');
        }

        if ($this->app_env === 'prod') {
            $this->captcha->verify($code);
        } else {
            $this->session->remove('captcha');
        }

        $admin = Admin::first(['admin_name' => $admin_name]);
        if (!$admin || !$admin->verifyPassword($password)) {
            return '账号或密码不正确';
        }

        if ($admin->status === Admin::STATUS_INIT) {
            return '账号还未激活';
        } elseif ($admin->status === Admin::STATUS_LOCKED) {
            return '账号已锁定';
        }

        $client_ip = $this->request->ip();

        if (!Ip::contains($admin->white_ip, $client_ip)) {
            return "$client_ip 地址未在白名单";
        }

        if ($admin->admin_id === 1) {
            $roles = ['admin'];
        } else {
            $roles = AdminRole::values('role_name', ['admin_id' => $admin->admin_id]);
            $roles = Role::values('role_name', ['enabled' => 1, 'role_name' => $roles]);
        }

        $claims = ['admin_id' => $admin->admin_id, 'admin_name' => $admin->admin_name, 'role' => implode(',', $roles)];
        $this->identity->set($claims);

        $session_id = $this->session->getId();
        if ($admin->session_id && $session_id !== $admin->session_id) {
            //同一个账号互踢
            // $this->session->destroy($admin->session_id);
        }

        $admin->login_ip = $client_ip;
        $admin->login_time = time();
        $admin->session_id = $session_id;
        $admin->update();

        $adminLoginLog = new AdminLoginLog();

        $adminLoginLog->admin_id = $admin->admin_id;
        $adminLoginLog->admin_name = $admin->admin_name;
        $adminLoginLog->client_ip = $client_ip;
        $adminLoginLog->client_udid = $udid;
        $adminLoginLog->user_agent = \substr($this->request->header('user-agent'), 0, 255);

        $adminLoginLog->create();
    }

    #[Authorize('user')]
    #[GetMapping(['/logout', '/admin/session/logout'])]
    public function logoutAction()
    {
        $this->session->destroy();

        return $this->response->redirect('/');
    }
}